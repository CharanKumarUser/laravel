<?php
namespace App\Jobs\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Mail, Config, Log, Http};
use Exception;
use Illuminate\Mail\Mailable;
class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected array $template;
    protected array $toAddresses;
    protected array $pairs;
    protected array $attachments;
    public function __construct(array $template, array $toAddresses, array $pairs, array $attachments)
    {
        $this->validateInputs($template, $toAddresses, $pairs, $attachments);
        $this->template = $template;
        $this->toAddresses = $toAddresses;
        $this->pairs = $pairs;
        $this->attachments = $attachments;
    }
    private function validateInputs(array $template, array $toAddresses, array $pairs, array $attachments): void
    {
        if (empty($template)) {
            throw new Exception('Template cannot be empty.');
        }
        if (empty($toAddresses)) {
            throw new Exception('At least one recipient address is required.');
        }
        foreach ($toAddresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address: {$address}");
            }
        }
        foreach ($attachments as $index => $attachment) {
            if (!isset($attachment['type'], $attachment['content']) || !in_array($attachment['type'], ['base64', 'file', 'url'])) {
                throw new Exception("Invalid attachment format at index {$index}.");
            }
        }
        if (isset($template['placeholders'])) {
            $placeholders = is_string($template['placeholders']) ? explode(',', $template['placeholders']) : (array) $template['placeholders'];
            foreach ($placeholders as $placeholder) {
                if (!array_key_exists(trim($placeholder), $pairs)) {
                    throw new Exception("Missing value for placeholder: {$placeholder}");
                }
            }
        }
    }
    public function handle(): void
    {
        try {
            $contentData = json_decode($this->template['content'] ?? '', true);
            if (!$contentData || !isset($contentData['components'])) {
                throw new Exception('Invalid or missing template content.');
            }
            $htmlContent = is_array($contentData['components']) ? implode('', $contentData['components']) : $contentData['components'];
            $styles = $contentData['styles'] ?? '';
            $subject = $this->template['subject'] ?? 'No Subject';
            $fromAddress = $this->template['from_address'] ?? Config::get('mail.from.address', 'noreply@gotit4all.com');
            $fromName = $this->template['from_name'] ?? Config::get('mail.from.name', env('APP_NAME', 'Got-It HR'));
            $mailer = $this->template['mailer'] ?? null;
            // Replace placeholders
            $htmlContent = $this->replaceValues($htmlContent, $this->pairs);
            $subject = $this->replaceValues($subject, $this->pairs);
            // If content has <html>, use it as-is; otherwise, wrap with HTML structure
            $fullHtml = strpos($htmlContent, '<html') === false ? <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <style>{$styles}</style>
</head>
<body>
{$htmlContent}
</body>
</html>
HTML : $htmlContent;
            $mailable = new CustomMailable($fullHtml, $subject, $this->toAddresses, $this->attachments, $fromAddress, $fromName);
            if ($mailer) {
                Mail::mailer($mailer)->send($mailable);
            } else {
                Mail::send($mailable);
            }
        } catch (Exception $e) {
            Log::error('SendMailJob failed', [
                'error' => $e->getMessage(),
                'template' => $this->template,
                'toAddresses' => $this->toAddresses,
                'pairs' => $this->pairs,
                'stacktrace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    protected function replaceValues(string $content, array $valuePairs): string
    {
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('/\{\:\{([a-zA-Z_]+)\}\:\}/', $content, $matches);
        foreach ($matches[1] as $index => $placeholder) {
            $search = $matches[0][$index];
            if (array_key_exists($placeholder, $valuePairs)) {
                $content = str_replace($search, $valuePairs[$placeholder], $content);
            } else {
                if (in_array($placeholder, ['place_service_items_here', 'place_email_content_here'])) {
                    $highlighted = htmlspecialchars($search);
                    $content = str_replace($search, $highlighted, $content);
                } else {
                    $highlighted = '<b style="background-color:red;color:white;border-radius:5px;padding:2px 3px 3px 3px">' . htmlspecialchars($search) . '</b>';
                    $content = str_replace($search, $highlighted, $content);
                }
            }
        }
        return $content;
    }
}
class CustomMailable extends Mailable
{
    protected string $content;
    protected string $subjectText;
    protected array $toAddresses;
    protected array $attachmentsData;
    protected string $fromAddress;
    protected string $fromName;
    public function __construct(string $content, string $subject, array $toAddresses, array $attachments, string $fromAddress, string $fromName)
    {
        $this->content = $content;
        $this->subjectText = $subject;
        $this->toAddresses = $toAddresses;
        $this->attachmentsData = $attachments;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }
    public function build()
    {
        $mail = $this->from($this->fromAddress, $this->fromName)
            ->to($this->toAddresses)
            ->subject($this->subjectText)
            ->html($this->content);
        foreach ($this->attachmentsData as $index => $attachment) {
            $extension = $attachment['extension'] ?? 'bin';
            $fileName = ($attachment['name'] ?? 'file') . '.' . $extension;
            try {
                if ($attachment['type'] === 'base64') {
                    $data = base64_decode($attachment['content'], true);
                    if ($data === false) {
                        throw new Exception("Failed to decode base64 attachment at index {$index}.");
                    }
                    $mail->attachData($data, $fileName, ['mime' => $this->getMimeType($extension)]);
                } elseif ($attachment['type'] === 'file') {
                    if (!file_exists($attachment['content'])) {
                        throw new Exception("Attachment file not found at index {$index}: {$attachment['content']}");
                    }
                    $mail->attach($attachment['content'], ['as' => $fileName, 'mime' => $this->getMimeType($extension)]);
                } elseif ($attachment['type'] === 'url') {
                    $response = Http::timeout(10)->get($attachment['content']);
                    if (!$response->successful()) {
                        throw new Exception("Failed to download attachment from URL at index {$index}: {$attachment['content']}");
                    }
                    $mail->attachData($response->body(), $fileName, ['mime' => $this->getMimeType($extension)]);
                }
            } catch (Exception $e) {
                Log::error('Attachment processing failed', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'attachment' => $attachment,
                ]);
                throw $e;
            }
        }
        return $mail;
    }
    private function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
        ];
        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}

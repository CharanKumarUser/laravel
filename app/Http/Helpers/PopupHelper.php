<?php
namespace App\Http\Helpers;
use Exception;
use App\Facades\Random;
use Illuminate\Support\Facades\{Config, Log};
use Illuminate\Support\Str;
/**
 * Helper class for generating secure, dynamic, and visually appealing popup form HTML for HRM software.
 * Supports all field types, nested fields, tabs, repeaters, and steppers with robust error handling and clean UI.
 */
class PopupHelper
{
    // Constants for default values and supported types
    private const DEFAULT_TOKEN_LENGTH = 16;
    private const DEFAULT_COL_SIZE = 12;
    private const DEFAULT_TEXTAREA_ROWS = 4;
    private const DEFAULT_TEXTAREA_COLS = 50;
    private const SUPPORTED_FIELD_TYPES = ['text', 'password', 'email', 'url', 'tel', 'number', 'date', 'datetime-local', 'time', 'month', 'week', 'color', 'range', 'search', 'textarea', 'select', 'multiselect', 'select-optgroup', 'checkbox', 'radio', 'switch', 'toggle', 'file', 'file-image', 'file-multi', 'file-upload', 'hidden', 'color-picker', 'range-slider', 'datetime', 'submit', 'button', 'reset', 'email-multi', 'tel-intl', 'number-step', 'date-range', 'time-picker', 'rating', 'tags', 'richtext', 'json', 'dragger', 'repeater', 'tabs', 'stepper', 'date-time-range', 'color-swatch', 'raw', 'label', 'autocomplete', 'raw', 'hidden', 'stepper', 'repeater', 'tabs', 'dragger', 'label', 'span', 'strong', 'small', 'em', 'b', 'i', 'mark', 'abbr', 'cite', 'q', 'code', 'kbd', 'samp'];
    private const SUPPORTED_LABEL_TYPES = ['floating', 'normal'];
    private const SUPPORTED_LAYOUTS = ['stacked', 'inline'];
    /**
     * Generate form HTML with predefined fields, supporting tabs, steppers, and repeaters.
     *
     * @param string $token Skeleton token for validation.
     * @param array $fields Form field definitions.
     * @param string $labelType Label style ('floating' or 'normal').
     * @param array $options Additional form options (e.g., form classes, attributes).
     * @return string Generated HTML content.
     */
    public static function generateBuildForm(
        string $token,
        array $fields,
        string $labelType = 'floating',
        array $options = []
    ): string {
        try {
            if (empty($token)) {
                throw new Exception('Token cannot be empty');
            }
            if (empty($fields)) {
                throw new Exception('No fields provided for form generation');
            }
            $labelType = in_array(strtolower($labelType), self::SUPPORTED_LABEL_TYPES)
                ? strtolower($labelType)
                : 'floating';
            $formClasses = array_merge(['row', 'g-3'], $options['form_classes'] ?? []);
            $formAttributes = self::buildAttributes($options, [], '');
            return sprintf(
                '<div class="%s" %s><input type="hidden" name="save_token" value="%s">%s</div>',
                implode(' ', array_map('htmlspecialchars', $formClasses)),
                $formAttributes,
                htmlspecialchars($token, ENT_QUOTES, 'UTF-8'),
                self::generate($labelType, $fields)
            );
        } catch (Exception $e) {
            return self::renderError('Form generation failed: ' . $e->getMessage());
        }
    }
    /**
     * Generate form HTML based on field definitions.
     *
     * @param string $labelType Label style ('floating' or 'normal').
     * @param array $fields Form field definitions.
     * @return string Generated HTML content.
     */
    private static function generate(string $labelType, array $fields): string
    {
        try {
            $html = '';
            foreach ($fields as $index => $field) {
                if (!is_array($field)) {
                    continue;
                }
                if (!isset($field['type'])) {
                    $html .= self::renderError("Field at index {$index} is missing 'type' key", 'col-12');
                    continue;
                }
                $html .= self::generateField($field, $labelType, (string)$index);
            }
            return $html ?: self::renderError('No valid fields were provided', 'col-12');
        } catch (Exception $e) {
            return self::renderError('Form generation failed: ' . $e->getMessage(), 'col-12');
        }
    }
    /**
     * Generate HTML for a single form field.
     *
     * @param array $field Field definition.
     * @param string $labelType Label style ('floating' or 'normal').
     * @param string $index Field index for error reporting.
     * @return string Generated HTML for the field.
     */
    private static function generateField(array $field, string $labelType, string $index): string
    {
        try {
            $type = strtolower($field['type']);
            if (!in_array($type, self::SUPPORTED_FIELD_TYPES)) {
                throw new Exception("Invalid field type '{$type}' at index {$index}");
            }
            $name = $field['name'] ?? 'field_' . Str::random(8);
            $label = $field['label'] ?? ($name ? Str::title(str_replace('_', ' ', $name)) : '');
            $value = old($name, $field['value'] ?? '');
            $required = $field['required'] ?? false;
            $id = $field['id'] ?? Random::token(Config::get('skeleton.token_length', self::DEFAULT_TOKEN_LENGTH));
            $placeholder = $field['placeholder'] ?? $label;
            $colClass = !empty($field['col_class']) ? $field['col_class'] : '';
            $divClass = !empty($field['div_class']) ? $field['div_class'] : '';
            $tagClass = !empty($field['tag_class']) ? ' class="' . $field['tag_class'] . '"' : '';
            $wrapperClasses = array_merge(
                [$labelType === 'floating' ? 'float-input-control' : 'mb-3'],
                $field['wrapper_class'] ?? []
            );
            $inputClasses = array_merge(
                [
                    $labelType === 'floating' ? 'form-float-input' : (
                        in_array($type, ['select', 'multiselect', 'select-optgroup']) ? 'form-select' : (
                            in_array($type, ['checkbox', 'radio', 'switch', 'toggle']) ? 'form-check-input' : 'form-control'
                        )
                    )
                ],
                $field['class'] ?? []
            );
            $labelClasses = array_merge(
                [$labelType === 'floating' ? 'form-float-label' : 'form-label'],
                $field['label_class'] ?? []
            );
            $colClasses = self::generateColumnClasses($field['col'] ?? self::DEFAULT_COL_SIZE);
            $attributes = self::buildAttributes($field, $inputClasses, $placeholder);
            $html = self::generateFieldWrapper($type, $colClasses, $wrapperClasses, $divClass, $colClass);
            switch ($type) {
                case 'raw':
                    if (!isset($field['html']) || !is_string($field['html'])) {
                        throw new Exception("Raw field '{$name}' at index {$index} requires 'html' string");
                    }
                    $html .= $field['html'];
                    break;
                case 'repeater':
                    $html .= self::generateRepeater($field, $labelType, $index);
                    break;
                case 'stepper':
                    $html .= self::generateStepper($field, $labelType, $index);
                    break;
                case 'tabs':
                    $html .= self::generateTabs($field, $labelType, $index);
                    break;
                case 'dragger':
                    $html .= self::generateDragger($field, $labelType, $index);
                    break;
                case 'span':
                case 'strong':
                case 'small':
                case 'label':
                case 'em':
                case 'b':
                case 'i':
                case 'mark':
                case 'abbr':
                case 'cite':
                case 'q':
                case 'code':
                case 'kbd':
                case 'samp':
                    $html .= sprintf(
                        '<%1$s%2$s>%3$s</%1$s>',
                        htmlspecialchars($type),
                        !empty($tagClass) ? $tagClass : '',
                        htmlspecialchars($label ?? '')
                    );
                    break;
                case 'text':
                case 'password':
                case 'email':
                case 'file':
                case 'url':
                case 'tel':
                case 'number':
                case 'date':
                case 'datetime-local':
                case 'time':
                case 'month':
                case 'week':
                case 'color':
                case 'range':
                case 'search':
                case 'time-picker':
                case 'autocomplete':
                    $html .= self::generateInputField($type, $id, $name, $value, $attributes, $label, $required, $labelType, $labelClasses);
                    break;
                case 'textarea':
                case 'richtext':
                case 'json':
                    $rows = $field['rows'] ?? self::DEFAULT_TEXTAREA_ROWS;
                    $cols = $field['cols'] ?? self::DEFAULT_TEXTAREA_COLS;
                    $extraAttrs = $type === 'richtext' ? ' data-richtext="true"' : ($type === 'json' ? ' data-json="true"' : '');
                    $value = $type === 'json' && is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;
                    $html .= $labelType === 'floating'
                        ? sprintf(
                            '<textarea id="%s" name="%s" rows="%s" cols="%s" %s>%s</textarea>%s',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $rows,
                            $cols,
                            $attributes . $extraAttrs,
                            htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                            self::generateLabel($id, $label, $required, $labelClasses)
                        )
                        : self::generateLabel($id, $label, $required, $labelClasses) . sprintf(
                            '<textarea id="%s" name="%s" rows="%s" cols="%s" %s>%s</textarea>',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $rows,
                            $cols,
                            $attributes . $extraAttrs,
                            htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                        );
                    break;
                case 'select':
                case 'multiselect':
                    $multiple = $type === 'multiselect' || (isset($field['attr']['multiple']) && $field['attr']['multiple']);
                    if (!isset($field['options']) || !is_array($field['options'])) {
                        throw new Exception("{$type} field '{$name}' at index {$index} requires options array");
                    }
                    $html .= $labelType === 'floating'
                        ? sprintf(
                            '<select id="%s" name="%s%s" %s>%s</select>%s',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $multiple ? '[]' : '',
                            $attributes,
                            self::generateOptions($field['options'], $value, $multiple, $name, $field['option_set'] ?? []),
                            self::generateLabel($id, $label, $required, $labelClasses)
                        )
                        : self::generateLabel($id, $label, $required, $labelClasses) . sprintf(
                            '<select id="%s" name="%s%s" %s>%s</select>',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $multiple ? '[]' : '',
                            $attributes,
                            self::generateOptions($field['options'], $value, $multiple, $name, $field['option_set'] ?? [])
                        );
                    break;
                case 'select-optgroup':
                    if (!isset($field['optgroups']) || !is_array($field['optgroups'])) {
                        throw new Exception("Select-optgroup field '{$name}' at index {$index} requires optgroups array");
                    }
                    $multiple = isset($field['attr']['multiple']) && $field['attr']['multiple'];
                    $html .= $labelType === 'floating'
                        ? sprintf(
                            '<select id="%s" name="%s%s" %s>%s</select>%s',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $multiple ? '[]' : '',
                            $attributes,
                            self::generateOptgroups($field['optgroups'], $value, $multiple, $name),
                            self::generateLabel($id, $label, $required, $labelClasses)
                        )
                        : self::generateLabel($id, $label, $required, $labelClasses) . sprintf(
                            '<select id="%s" name="%s%s" %s>%s</select>',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $multiple ? '[]' : '',
                            $attributes,
                            self::generateOptgroups($field['optgroups'], $value, $multiple, $name)
                        );
                    break;
                case 'checkbox':
                case 'radio':
                    if (!isset($field['options']) || !is_array($field['options'])) {
                        throw new Exception("{$type} field '{$name}' at index {$index} requires options array");
                    }
                    $layout = in_array($field['layout'] ?? 'stacked', self::SUPPORTED_LAYOUTS) ? $field['layout'] : 'stacked';
                    $html .= sprintf('<div class="form-check-%s-group %s">', $type, $layout === 'inline' ? 'd-flex gap-3' : '');
                    foreach ($field['options'] as $optValue => $optLabel) {
                        $optId = htmlspecialchars($id . '-' . Str::slug($optValue));
                        $checked = is_array($value) ? in_array((string)$optValue, $value, true) : ((string)$optValue === (string)$value);
                        $html .= sprintf(
                            '<div class="form-check %s"><input class="form-check-input" type="%s" id="%s" name="%s" value="%s" %s %s>%s</div>',
                            $layout === 'inline' ? 'form-check-inline' : '',
                            $type,
                            $optId,
                            htmlspecialchars($name),
                            htmlspecialchars($optValue),
                            $attributes,
                            $checked ? 'checked' : '',
                            self::generateLabel($optId, $optLabel, false, ['form-check-label'])
                        );
                    }
                    $html .= '</div>' . ($label ? self::generateLabel($id, $label, $required, $labelClasses) : '');
                    break;
                case 'switch':
                case 'toggle':
                    $html .= sprintf(
                        '<div class="form-check form-switch"><input type="checkbox" class="form-check-input" id="%s" name="%s" %s %s>%s</div>',
                        htmlspecialchars($id),
                        htmlspecialchars($name),
                        $attributes,
                        $value ? 'checked' : '',
                        self::generateLabel($id, $label, $required, ['form-check-label'])
                    );
                    break;
                case 'hidden':
                    $html .= sprintf(
                        '<input type="hidden" id="%s" name="%s" value="%s" %s>',
                        htmlspecialchars($id),
                        htmlspecialchars($name),
                        htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                        $attributes
                    );
                    break;
                case 'color-picker':
                case 'color-swatch':
                    $html .= self::generateInputField(
                        'color',
                        $id,
                        $name,
                        $value ?: '#000000',
                        $attributes . ($type === 'color-swatch' ? ' data-swatch="true"' : ''),
                        $label,
                        $required,
                        $labelType,
                        $labelClasses
                    );
                    break;
                case 'range-slider':
                    $html .= $labelType === 'floating'
                        ? sprintf(
                            '<input type="range" id="%s" name="%s" min="%s" max="%s" step="%s" value="%s" %s>%s',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $field['min'] ?? 0,
                            $field['max'] ?? 100,
                            $field['step'] ?? 1,
                            htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                            $attributes,
                            self::generateLabel($id, $label, $required, $labelClasses)
                        )
                        : self::generateLabel($id, $label, $required, $labelClasses) . sprintf(
                            '<input type="range" id="%s" name="%s" min="%s" max="%s" step="%s" value="%s" %s>',
                            htmlspecialchars($id),
                            htmlspecialchars($name),
                            $field['min'] ?? 0,
                            $field['max'] ?? 100,
                            $field['step'] ?? 1,
                            htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                            $attributes
                        );
                    break;
                case 'datetime':
                    $html .= self::generateInputField('datetime-local', $id, $name, $value, $attributes, $label, $required, $labelType, $labelClasses);
                    break;
                case 'submit':
                case 'button':
                case 'reset':
                    $btnClass = $type === 'submit' ? 'btn-primary' : ($type === 'reset' ? 'btn-danger' : 'btn-secondary');
                    $html .= sprintf(
                        '<button type="%s" id="%s" name="%s" class="btn %s btn-sm" %s>%s</button>',
                        $type,
                        htmlspecialchars($id),
                        htmlspecialchars($name),
                        $btnClass,
                        $attributes,
                        htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                    );
                    break;
                default:
                    throw new Exception("Unsupported field type '{$type}' for field '{$name}' at index {$index}");
            }
            return $html . '</div></div>';
        } catch (Exception $e) {
            return self::renderError("Field generation failed: {$e->getMessage()}", 'col-12');
        }
    }
    /**
     * Render an error message as an alert.
     *
     * @param string $message Error message.
     * @param string $wrapperClass Optional wrapper class.
     * @return string HTML for the error alert.
     */
    private static function renderError(string $message, string $wrapperClass = ''): string
    {
        return sprintf(
            '<div class="%s"><div class="alert alert-warning alert-dismissible fade show" role="alert">%s<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div></div>',
            $wrapperClass,
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        );
    }
    /**
     * Generate wrapper HTML for a field.
     *
     * @param string $type Field type.
     * @param array $colClasses Column classes.
     * @param array $wrapperClasses Wrapper classes.
     * @param string $colClass Wrapper classes.
     * @param string $tagClass Wrapper classes.
     * @return string Generated wrapper HTML.
     */
    private static function generateFieldWrapper(string $type, array $colClasses, array $wrapperClasses, string $divClass, string $colClass): string
    {
        $inlineTags = ['raw', 'hidden', 'stepper', 'repeater', 'tabs', 'dragger', 'label', 'span', 'strong', 'small', 'em', 'b', 'i', 'mark', 'abbr', 'cite', 'q', 'code', 'kbd', 'samp'];
        return in_array($type, $inlineTags, true)
            ? sprintf(
                '<div class="%s %s"><div%s>',
                implode(' ', $colClasses),
                !empty($colClass) ? $colClass : '',
                !empty($divClass) ? ' class="' . $divClass . '"' : ''
            )
            : sprintf(
                '<div class="%s %s"><div class="%s %s">',
                implode(' ', $colClasses),
                !empty($colClass) ? $colClass : '',
                implode(' ', $wrapperClasses),
                !empty($divClass) ? $divClass : ''
            );
    }
    /**
     * Generate input field HTML for common input types.
     *
     * @param string $type Input type.
     * @param string $id Field ID.
     * @param string $name Field name.
     * @param mixed $value Field value.
     * @param string $attributes Additional attributes.
     * @param string $label Label text.
     * @param bool $required Whether the field is required.
     * @param string $labelType Label style ('floating' or 'normal').
     * @param array $labelClasses CSS classes for the label.
     * @return string Generated HTML.
     */
    private static function generateInputField(
        string $type,
        string $id,
        string $name,
        $value,
        string $attributes,
        string $label,
        bool $required,
        string $labelType,
        array $labelClasses
    ): string {
        return $labelType === 'floating'
            ? sprintf(
                '<input type="%s" id="%s" name="%s" value="%s" %s>%s',
                $type,
                htmlspecialchars($id),
                htmlspecialchars($name),
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                $attributes,
                self::generateLabel($id, $label, $required, $labelClasses)
            )
            : self::generateLabel($id, $label, $required, $labelClasses) . sprintf(
                '<input type="%s" id="%s" name="%s" value="%s" %s>',
                $type,
                htmlspecialchars($id),
                htmlspecialchars($name),
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                $attributes
            );
    }
    /**
     * Generate label HTML for a field.
     *
     * @param string $id Field ID.
     * @param string $label Label text.
     * @param bool $required Whether the field is required.
     * @param array $classes CSS classes for the label.
     * @return string Generated label HTML.
     */
    private static function generateLabel(string $id, string $label, bool $required, array $classes): string
    {
        return $label ? sprintf(
            '<label for="%s" class="%s">%s%s</label>',
            htmlspecialchars($id),
            implode(' ', array_map('htmlspecialchars', $classes)),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
            $required ? '<span class="text-danger ms-1">*</span>' : ''
        ) : '';
    }
    /**
     * Generate options HTML for a select field.
     *
     * @param array $options Options array.
     * @param mixed $selectedValue Selected value(s).
     * @param bool $multiple Whether the select is multiple.
     * @param string $fieldName Field name for debugging.
     * @param array $optionSet Options array.
     * @return string Generated options HTML.
     */
    private static function generateOptions(array $options, $selectedValue, bool $multiple, string $fieldName, ?array $optionSet): string
    {
        try {
            $html = '';
            $selectedValues = $multiple ? (array)$selectedValue : [(string)$selectedValue];
            if(!empty($optionSet)){
                foreach ($options as $option) {
                $value = isset($option['value']) ? $option['value'] : '';
                $view = isset($option['view']) ? $option['view'] : '';
                $id = isset($option['id']) ? ' data-id="'.$option['id'].'"' : '';
                $group = isset($option['group']) ? ' data-group="'.$option['group'].'"' : '';
                $avatar = isset($option['avatar']) ? ' data-avatar="'.$option['avatar'].'"' : '';
                $html .= sprintf(
                    '<option value="%s"%s%s%s>%s</option>',
                    htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($id, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($group, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($view, ENT_QUOTES, 'UTF-8')
                );
            }
            return $html;
            }
            foreach ($options as $value => $option) {
                $text = is_array($option) ? ($option[0] ?? $option['text'] ?? $value) : $option;
                $isSelected = in_array((string)$value, array_map('strval', $selectedValues), true);
                $html .= sprintf(
                    '<option value="%s" %s>%s</option>',
                    htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                    $isSelected ? 'selected' : '',
                    htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
                );
            }
            return $html;
        } catch (Exception $e) {
            Log::warning("Failed to generate options for field '{$fieldName}': {$e->getMessage()}");
            return '';
        }
    }
    /**
     * Generate optgroups HTML for a select field.
     *
     * @param array $optgroups Optgroups array.
     * @param mixed $selectedValue Selected value(s).
     * @param bool $multiple Whether the select is multiple.
     * @param string $fieldName Field name for debugging.
     * @return string Generated optgroups HTML.
     */
    private static function generateOptgroups(array $optgroups, $selectedValue, bool $multiple, string $fieldName): string
    {
        try {
            $html = '';
            $selectedValues = $multiple ? (array)$selectedValue : [(string)$selectedValue];
            foreach ($optgroups as $groupLabel => $options) {
                if (!is_array($options)) {
                    continue;
                }
                $html .= sprintf('<optgroup label="%s">', htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'));
                foreach ($options as $value => $text) {
                    $isSelected = in_array((string)$value, array_map('strval', $selectedValues), true);
                    $html .= sprintf(
                        '<option value="%s" %s>%s</option>',
                        htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                        $isSelected ? 'selected' : '',
                        htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
                    );
                }
                $html .= '</optgroup>';
            }
            return $html;
        } catch (Exception $e) {
            Log::warning("Failed to generate optgroups for field '{$fieldName}': {$e->getMessage()}");
            return '';
        }
    }
    /**
     * Build HTML attributes for a field.
     *
     * @param array $field Field definition.
     * @param array $inputClasses CSS classes for the input.
     * @param string $placeholder Placeholder text.
     * @return string Generated attributes string.
     */
    private static function buildAttributes(array $field, array $inputClasses, string $placeholder): string
    {
        try {
            $attrs = [];
            if (!empty($inputClasses)) {
                $attrs[] = sprintf('class="%s"', implode(' ', array_map('htmlspecialchars', $inputClasses)));
            }
            if ($field['required'] ?? false) {
                $attrs[] = 'required';
            }
            if ($placeholder && $placeholder !== 'none') {
                $attrs[] = sprintf('placeholder="%s"', htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'));
            }
            if (isset($field['pattern'])) {
                $attrs[] = sprintf('pattern="%s"', htmlspecialchars($field['pattern'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($field['minlength'])) {
                $attrs[] = sprintf('minlength="%s"', htmlspecialchars($field['minlength'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($field['maxlength'])) {
                $attrs[] = sprintf('maxlength="%s"', htmlspecialchars($field['maxlength'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($field['validate'])) {
                $attrs[] = sprintf('data-validate="%s"', htmlspecialchars($field['validate'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($field['validation_rules']) && is_array($field['validation_rules'])) {
                $attrs[] = sprintf('data-validation-rules="%s"', htmlspecialchars(json_encode($field['validation_rules'], JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8'));
            }
            if (isset($field['attr']) && is_array($field['attr'])) {
                foreach ($field['attr'] as $key => $val) {
                    if ($key === 'multiple' && $val) {
                        $attrs[] = 'multiple';
                    } else {
                        $attrs[] = sprintf(
                            '%s="%s"',
                            htmlspecialchars($key),
                            htmlspecialchars(is_array($val) ? json_encode($val, JSON_THROW_ON_ERROR) : (string)$val, ENT_QUOTES, 'UTF-8')
                        );
                    }
                }
            }
            return implode(' ', $attrs);
        } catch (Exception $e) {
            Log::warning("Failed to build attributes: {$e->getMessage()}");
            return '';
        }
    }
    /**
     * Generate column classes based on column size.
     *
     * @param int|array|null $col Column size (1-12 as int, or breakpoint => size as array).
     * @return array Array of column classes.
     */
    private static function generateColumnClasses($col): array
    {
        try {
            $breakpoints = ['sm', 'md', 'lg', 'xl', 'xxl'];
            $colClasses = [];
            if (is_numeric($col)) {
                $size = max(1, min((int)$col, 12)); // Ensure between 1 and 12
                foreach ($breakpoints as $breakpoint) {
                    $colClasses[] = "col-{$breakpoint}-{$size}";
                }
            } elseif (is_array($col)) {
                foreach ($col as $breakpoint => $size) {
                    if (in_array($breakpoint, $breakpoints, true) && is_numeric($size)) {
                        $size = max(1, min((int)$size, 12));
                        $colClasses[] = "col-{$breakpoint}-{$size}";
                    }
                }
            }
            return $colClasses ?: ['col-12'];
        } catch (\Throwable $e) {
            return ['col-12'];
        }
    }
    /**
     * Generate HTML for a repeater field.
     *
     * @param array $field Field definition.
     * @param string $labelType Label style ('floating' or 'normal').
     * @param string $index Field index for error reporting.
     * @return string Generated HTML for the repeater.
     */
    private static function generateRepeater(array $field, string $labelType, string $index): string
    {
        try {
            if (!isset($field['fields']) || !is_array($field['fields'])) {
                throw new Exception("Repeater field at index {$index} requires 'fields' array");
            }
            $inputName = $field['name'] ?? 'repeater_' . Str::random(8);
            $dataType = $field['set'] ?? 'pair';
            $dataPrevious = $field['value'] ?? '';
            $containerClasses = $field['container_class'] ?? ['d-flex', 'flex-row', 'gap-3', 'w-100', 'align-items-end', 'mt-3'];
            $html = sprintf(
                '<div data-repeater-container data-input="%s" data-type="%s" data-previous=\'%s\'><div data-repeater class="%s">',
                htmlspecialchars($inputName, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($dataType, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($dataPrevious, ENT_QUOTES, 'UTF-8'),
                implode(' ', array_map('htmlspecialchars', $containerClasses))
            );
            foreach ($field['fields'] as $subFieldIndex => $subField) {
                if (!isset($subField['type'])) {
                    throw new Exception("Repeater subfield at index {$subFieldIndex} is missing 'type' key");
                }
                $subFieldType = strtolower($subField['type']);
                if (!in_array($subFieldType, ['select', 'number', 'text', 'textarea', 'date', 'time', 'email', 'color', 'url', 'tel', 'radio', 'checkbox'])) {
                    throw new Exception("Unsupported repeater subfield type '{$subFieldType}' at index {$index}-{$subFieldIndex}");
                }
                $subFieldName = $subField['name'] ?? 'subfield_' . Str::random(8);
                $subFieldLabel = $subField['label'] ?? Str::title(str_replace('_', ' ', $subFieldName));
                $subFieldValue = old($subFieldName, $subField['value'] ?? '');
                $subFieldRequired = $subField['required'] ?? false;
                $subFieldId = $subField['id'] ?? Random::token(Config::get('skeleton.token_length', self::DEFAULT_TOKEN_LENGTH));
                $subFieldPlaceholder = $subField['placeholder'] ?? $subFieldLabel;
                $subFieldClasses = array_merge(
                    [$labelType === 'floating' ? 'form-float-input' : ($subFieldType === 'select' ? 'form-select' : 'form-control')],
                    $subField['class'] ?? []
                );
                $subFieldLabelClasses = array_merge(
                    [$labelType === 'floating' ? 'form-float-label' : 'form-label'],
                    $subField['label_class'] ?? []
                );
                $subFieldAttributes = self::buildAttributes($subField, $subFieldClasses, $subFieldPlaceholder);
                $html .= '<div class="float-input-control flex-grow-1">';
                if ($subFieldType === 'select') {
                    if (!isset($subField['options']) || !is_array($subField['options'])) {
                        throw new Exception("Select subfield '{$subFieldName}' in repeater at index {$index}-{$subFieldIndex} requires 'options' array");
                    }
                    $html .= sprintf(
                        '<select name="%s" id="%s" %s>%s</select>%s',
                        htmlspecialchars($subFieldName, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($subFieldId, ENT_QUOTES, 'UTF-8'),
                        $subFieldAttributes,
                        self::generateOptions($subField['options'], $subFieldValue, false, $subFieldName, $subField['option_set'] ?? []),
                        self::generateLabel($subFieldId, $subFieldLabel, $subFieldRequired, $subFieldLabelClasses)
                    );
                } elseif (in_array($subFieldType, ['select', 'number', 'text', 'textarea', 'date', 'time', 'email', 'color', 'url', 'tel', 'radio', 'checkbox'])) {
                    $html .= sprintf(
                        '<input type="%s" name="%s" id="%s" value="%s" %s>%s',
                        htmlspecialchars($subFieldType, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($subFieldName, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($subFieldId, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($subFieldValue, ENT_QUOTES, 'UTF-8'),
                        $subFieldAttributes,
                        self::generateLabel($subFieldId, $subFieldLabel, $subFieldRequired, $subFieldLabelClasses)
                    );
                } else {
                    $html .= sprintf(
                        '<input type="number" name="%s" id="%s" value="%s" %s>%s',
                        htmlspecialchars($subFieldName, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($subFieldId, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($subFieldValue, ENT_QUOTES, 'UTF-8'),
                        $subFieldAttributes,
                        self::generateLabel($subFieldId, $subFieldLabel, $subFieldRequired, $subFieldLabelClasses)
                    );
                }
                $html .= '</div>';
            }
            $html .= '<button data-repeater-add type="button"><i class="ti ti-plus"></i></button></div></div>';
            return $html;
        } catch (Exception $e) {
            return self::renderError("Repeater generation failed: {$e->getMessage()}");
        }
    }
    /**
     * Generate HTML for a stepper field.
     *
     * @param array $field Field definition.
     * @param string $labelType Label style ('floating' or 'normal').
     * @param string $index Field index for error reporting.
     * @return string Generated HTML for the stepper.
     */
    private static function generateStepper(array $field, string $labelType, string $index): string
    {
        try {
            if (!isset($field['steps']) || !is_array($field['steps'])) {
                throw new Exception("Stepper field at index {$index} requires 'steps' array");
            }
            $stepperType = $field['stepper'] ?? 'linear';
            $progressType = $field['progress'] ?? 'bar+icon';
            $progressColor = $field['color'] ?? '#00b4af';
            $submitBtnText = $field['submit_text'] ?? 'Submit';
            $btnClass = $field['btn_class'] ?? 'btn btn-primary';
            $containerClasses = array_merge(['stepper-container'], $field['container_class'] ?? []);
            $html = sprintf(
                '<div data-stepper-container data-stepper-type="%s" data-progress-type="%s" data-progress-color="%s" data-submit-btn-text="%s" data-btn-class="%s" class="%s">',
                htmlspecialchars($stepperType, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($progressType, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($progressColor, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($submitBtnText, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8'),
                implode(' ', array_map('htmlspecialchars', $containerClasses))
            );
            foreach ($field['steps'] as $stepIndex => $step) {
                if (!isset($step['title']) || !isset($step['fields']) || !is_array($step['fields'])) {
                    throw new Exception("Step at index {$stepIndex} in stepper at index {$index} requires 'title' and 'fields' array");
                }
                $stepTitle = $step['title'];
                $stepIcon = $step['icon'] ?? 'fa-circle';
                $stepClasses = array_merge(['row', 'g-3', 'pb-4'], $step['container_class'] ?? []);
                $html .= sprintf(
                    '<div data-step data-title="%s" data-icon="%s"><div class="%s">',
                    htmlspecialchars($stepTitle, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($stepIcon, ENT_QUOTES, 'UTF-8'),
                    implode(' ', array_map('htmlspecialchars', $stepClasses))
                );
                foreach ($step['fields'] as $subFieldIndex => $subField) {
                    if (!isset($subField['type'])) {
                        throw new Exception("Subfield at index {$subFieldIndex} in step '{$stepTitle}' at index {$index} is missing 'type' key");
                    }
                    $html .= self::generateField($subField, $labelType, "{$index}-step-{$stepIndex}-{$subFieldIndex}");
                }
                $html .= '</div></div>';
            }
            $html .= '</div>';
            return $html;
        } catch (Exception $e) {
            return self::renderError("Stepper generation failed: {$e->getMessage()}");
        }
    }
    /**
     * Generate HTML for a tabs field.
     *
     * @param array $field Field definition.
     * @param string $labelType Label style ('floating' or 'normal').
     * @param string $index Field index for error reporting.
     * @return string Generated HTML for the tabs.
     */
    private static function generateTabs(array $field, string $labelType, string $index): string
    {
        try {
            if (!isset($field['tabs']) || !is_array($field['tabs'])) {
                throw new Exception("Tabs field at index {$index} requires 'tabs' array");
            }
            $tabId = $field['id'] ?? 'tabs_' . Str::random(8);
            $tabType = $field['tab'] ?? 'nav-tabs';
            $containerClasses = array_merge(['nav', $tabType, 'mb-3', 'data-skl-action'], $field['class'] ?? []);
            $html = sprintf('<div class="tabs-container" id="%s">', htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8'));
            $html .= sprintf('<ul class="%s" role="tablist">', implode(' ', array_map('htmlspecialchars', $containerClasses)));
            $firstTab = true;
            foreach ($field['tabs'] as $tabIndex => $tab) {
                if (!isset($tab['title']) || !isset($tab['fields']) || !is_array($tab['fields'])) {
                    throw new Exception("Tab at index {$tabIndex} in tabs at index {$index} requires 'title' and 'fields' array");
                }
                $tabPaneId = $tab['id'] ?? "{$tabId}-pane-{$tabIndex}";
                $activeClass = $firstTab ? ' active' : '';
                $ariaSelected = $firstTab ? 'true' : 'false';
                $html .= sprintf(
                    '<li class="nav-item" role="presentation"><button class="nav-link%s" id="%s-tab" data-bs-toggle="tab" data-bs-target="#%s" type="button" role="tab" aria-controls="%s" aria-selected="%s">%s</button></li>',
                    $activeClass,
                    htmlspecialchars($tabPaneId, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($tabPaneId, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($tabPaneId, ENT_QUOTES, 'UTF-8'),
                    $ariaSelected,
                    htmlspecialchars($tab['title'], ENT_QUOTES, 'UTF-8')
                );
                $firstTab = false;
            }
            $html .= '</ul><div class="tab-content">';
            $firstTab = true;
            foreach ($field['tabs'] as $tabIndex => $tab) {
                $tabPaneId = $tab['id'] ?? "{$tabId}-pane-{$tabIndex}";
                $activeClass = $firstTab ? ' show active' : '';
                $tabClasses = array_merge(['tab-pane', 'fade'], $tab['container_class'] ?? []);
                $html .= sprintf(
                    '<div class="%s%s" id="%s" role="tabpanel" aria-labelledby="%s-tab"><div class="row g-3">',
                    implode(' ', array_map('htmlspecialchars', $tabClasses)),
                    $activeClass,
                    htmlspecialchars($tabPaneId, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($tabPaneId, ENT_QUOTES, 'UTF-8')
                );
                foreach ($tab['fields'] as $subFieldIndex => $subField) {
                    if (!isset($subField['type'])) {
                        throw new Exception("Subfield at index {$subFieldIndex} in tab '{$tab['title']}' at index {$index} is missing 'type' key");
                    }
                    $html .= self::generateField($subField, $labelType, "{$index}-tab-{$tabIndex}-{$subFieldIndex}");
                }
                $html .= '</div></div>';
                $firstTab = false;
            }
            $html .= '</div></div>';
            return $html;
        } catch (Exception $e) {
            return self::renderError("Tabs generation failed: {$e->getMessage()}");
        }
    }
    /**
     * Generate HTML for a dragger field with source on left and target on right.
     *
     * @param array $field Field definition containing pre-prepared source and target HTML.
     * @param string $labelType Label style ('floating' or 'normal').
     * @param string $index Field index for error reporting.
     * @return string Generated HTML for the dragger.
     */
    private static function generateDragger(array $field, string $labelType, string $index): string
    {
        try {
            if (!isset($field['source']['html']) || !isset($field['target']['html'])) {
                throw new Exception("Dragger field at index {$index} requires 'source.html' and 'target.html' keys");
            }
            $containerClasses = array_merge(['px-2', 'rounded-3', 'pb-2', 'border', 'border-2'], $field['container_class'] ?? []);
            $label = $field['label'] ?? 'Drag and drop modules for this plan';
            $inputName = $field['name'] ?? 'Drag and drop modules for this plan';
            $maxItems = $field['max'] ?? 'u';
            $labelClasses = array_merge(['sf-12', 'fw-bold'], $field['label_class'] ?? []);
            $html = sprintf('<div class="%s">', implode(' ', array_map('htmlspecialchars', $containerClasses)));
            $html .= sprintf(
                '<div><span class="%s">%s</span></div>',
                implode(' ', array_map('htmlspecialchars', $labelClasses)),
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            );
            $html .= '<div data-drag-container class="mt-1 d-flex flex-row gap-3">';
            $sourceClasses = array_merge(['drag-area', 'flex-grow-1'], $field['source']['class'] ?? []);
            $sourceInputString = $field['source']['input_string'] ?? '.area_1_values';
            $sourceInputSum = $field['source']['input_sum'] ?? '.area_1_sum';
            $sourceSeparator = $field['source']['separator'] ?? ',';
            $html .= sprintf(
                '<div data-drag-area data-max="%s" data-input-string="%s" data-input-sum="%s" data-seperator="%s" class="%s">%s</div>',
                htmlspecialchars($maxItems, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($sourceInputString, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($sourceInputSum, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($sourceSeparator, ENT_QUOTES, 'UTF-8'),
                implode(' ', array_map('htmlspecialchars', $sourceClasses)),
                $field['source']['html']
            );
            $targetClasses = array_merge(['drag-area', 'flex-grow-1'], $field['target']['class'] ?? []);
            $targetInputString = $field['target']['input_string'] ?? '.dropped-module-ids';
            $inputSelector = trim(str_replace('#', '', str_replace('.', '', $field['target']['input_string'] ?? '.dropped-module-ids')));
            $targetInputSum = $field['target']['input_sum'] ?? '.dropped-module-sum';
            $targetSeparator = $field['target']['separator'] ?? ',';
            $html .= sprintf(
                '<div data-drag-area data-max="%s" data-input-string="%s" data-input-sum="%s" data-seperator="%s" class="%s">%s</div>',
                htmlspecialchars($maxItems, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($targetInputString, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($targetInputSum, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($targetSeparator, ENT_QUOTES, 'UTF-8'),
                implode(' ', array_map('htmlspecialchars', $targetClasses)),
                $field['target']['html']
            );
            $html .= '</div><input type="hidden" id="'.$inputSelector.'" class="'.$inputSelector.'" name="'.$inputName.'"></div>';
            return $html;
        } catch (Exception $e) {
            return self::renderError("Dragger generation failed: {$e->getMessage()}");
        }
    }
}

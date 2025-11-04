import ace from 'ace-builds';
import 'ace-builds/src-noconflict/theme-monokai';
import 'ace-builds/src-noconflict/mode-php';
import 'ace-builds/src-noconflict/mode-html';
import 'ace-builds/src-noconflict/mode-css';
import 'ace-builds/src-noconflict/mode-javascript';
import 'ace-builds/src-noconflict/mode-sql';
import 'ace-builds/src-noconflict/mode-json';
import 'ace-builds/src-noconflict/ext-language_tools';
ace.config.set('basePath', '/vendor/ace');
ace.config.set('workerPath', '/vendor/ace');
export function code() {
  try {
    const languageModes = {
      php: 'ace/mode/php',
      html: 'ace/mode/html',
      css: 'ace/mode/css',
      js: 'ace/mode/javascript',
      sql: 'ace/mode/sql',
      json: 'ace/mode/json',
    };
    const codeElements = document.querySelectorAll('[data-code]');
    if (!codeElements.length) return;
    ace.config.set('enableBasicAutocompletion', true);
    ace.config.set('enableLiveAutocompletion', true);
    codeElements.forEach((element) => {
      const language = element.getAttribute('data-code')?.toLowerCase();
      const inputName = element.getAttribute('data-code-input');
      let rawValue = element.getAttribute('data-code-value') || '';
      if (!languageModes[language]) return;
      // Safely decode HTML entities in case JSON is encoded
      const htmlDecode = (str) => {
        const txt = document.createElement('textarea');
        txt.innerHTML = str;
        return txt.value;
      };
      let initialValue = '';
      try {
        const decoded = htmlDecode(rawValue);
        initialValue = JSON.parse(decoded); // Value was JSON-encoded
      } catch {
        initialValue = htmlDecode(rawValue); // Fallback: plain string
      }
      const wrapper = document.createElement('div');
      wrapper.style.position = 'relative';
      wrapper.style.width = '100%';
      const editorContainer = document.createElement('div');
      editorContainer.className = `ace-container-${language}`;
      editorContainer.style.width = '100%';
      editorContainer.style.height = '300px';
      editorContainer.style.border = 'none';
      editorContainer.style.borderRadius = '5px 5px 0px 0px';
      editorContainer.style.overflow = 'auto';
      const resizeHandle = document.createElement('div');
      resizeHandle.classList.add('code-resize-handle');
      wrapper.appendChild(editorContainer);
      wrapper.appendChild(resizeHandle);
      element.appendChild(wrapper);
      let hiddenInput = element.querySelector(`input[name="${inputName}"]`);
      if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = inputName || `code_${language}_input`;
        element.appendChild(hiddenInput);
      }
      const editor = ace.edit(editorContainer, {
        mode: languageModes[language],
        theme: 'ace/theme/monokai',
        value: initialValue,
        tabSize: 2,
        useSoftTabs: true,
        wrap: true,
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true,
        showPrintMargin: false,
        useWorker: true,
      });
      editor.session.on('change', () => {
        const val = editor.getValue();
        hiddenInput.value = JSON.stringify(val); // Re-encode to JSON string
        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
      });
      // Set initial value
      hiddenInput.value = JSON.stringify(editor.getValue());
      // Resizing logic
      let isResizing = false;
      let startY = 0;
      let startHeight = 0;
      resizeHandle.addEventListener('mousedown', (e) => {
        isResizing = true;
        startY = e.clientY;
        startHeight = editorContainer.offsetHeight;
        document.body.style.cursor = 'ns-resize';
        document.body.style.userSelect = 'none';
      });
      document.addEventListener('mousemove', (e) => {
        if (!isResizing) return;
        const newHeight = startHeight + (e.clientY - startY);
        if (newHeight > 100) {
          editorContainer.style.height = `${newHeight}px`;
          editor.resize();
        }
      });
      document.addEventListener('mouseup', () => {
        if (isResizing) {
          isResizing = false;
          document.body.style.cursor = '';
          document.body.style.userSelect = '';
        }
      });
    });
  } catch (e) {
    window?.general?.error?.('Error in code editor:', e);
  }
}

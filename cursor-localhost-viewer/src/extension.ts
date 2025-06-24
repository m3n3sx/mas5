import * as vscode from 'vscode';

interface CommonPort {
    name: string;
    port: number;
    description: string;
}

export function activate(context: vscode.ExtensionContext) {
    // Pokaż powiadomienie że extension się załadował
    vscode.window.showInformationMessage('🚀 Localhost Viewer Extension został załadowany!');
    console.log('Localhost Viewer extension is now active!');

    // Rejestracja komend
    let openLocalhost = vscode.commands.registerCommand('cursor-localhost-viewer.openLocalhost', async () => {
        vscode.window.showInformationMessage('Otwieram Localhost URL...');
        await openLocalhostUrl();
    });

    let openWordPress = vscode.commands.registerCommand('cursor-localhost-viewer.openWordPress', async () => {
        vscode.window.showInformationMessage('Otwieram WordPress...');
        await openWordPressSite();
    });

    let openCommonPorts = vscode.commands.registerCommand('cursor-localhost-viewer.openCommonPorts', async () => {
        vscode.window.showInformationMessage('Otwieram menu portów...');
        await showCommonPortsMenu();
    });

    // Dodaj prostą komendę testową
    let testCommand = vscode.commands.registerCommand('cursor-localhost-viewer.test', () => {
        vscode.window.showInformationMessage('✅ Extension działa poprawnie!');
    });

    context.subscriptions.push(openLocalhost, openWordPress, openCommonPorts, testCommand);
}

async function openLocalhostUrl() {
    const config = vscode.workspace.getConfiguration('cursorLocalhostViewer');
    const defaultPort = config.get<number>('defaultPort', 3000);

    const url = await vscode.window.showInputBox({
        prompt: 'Podaj URL localhost (np. http://localhost:3000)',
        value: `http://localhost:${defaultPort}`,
        placeHolder: 'http://localhost:3000'
    });

    if (url) {
        await openInFullBrowser(url);
    }
}

async function openWordPressSite() {
    const config = vscode.workspace.getConfiguration('cursorLocalhostViewer');
    const wordPressPort = config.get<number>('wordPressPort', 10018);

    const wordPressUrl = `http://localhost:${wordPressPort}/wp-admin/`;
    
    // Sprawdź czy WordPress działa
    const isWordPressRunning = await checkIfServiceRunning(wordPressPort.toString());
    
    if (!isWordPressRunning) {
        const result = await vscode.window.showWarningMessage(
            `WordPress na porcie ${wordPressPort} nie odpowiada. Czy chcesz spróbować inny port?`,
            'Tak', 'Nie'
        );
        
        if (result === 'Tak') {
            await openLocalhostUrl();
            return;
        }
    }

    await openInFullBrowser(wordPressUrl);
}

async function showCommonPortsMenu() {
    const config = vscode.workspace.getConfiguration('cursorLocalhostViewer');
    const commonPorts = config.get<CommonPort[]>('commonPorts', []);

    const items = commonPorts.map(port => ({
        label: `${port.name} (${port.port})`,
        description: port.description,
        detail: `http://localhost:${port.port}`,
        port: port.port
    }));

    const selected = await vscode.window.showQuickPick(items, {
        placeHolder: 'Wybierz port do otwarcia...'
    });

    if (selected) {
        const url = `http://localhost:${selected.port}`;
        await openInFullBrowser(url);
    }
}

async function openInFullBrowser(url: string) {
    try {
        // Sprawdź czy URL jest dostępny
        const urlObj = new URL(url);
        const isAvailable = await checkIfServiceRunning(urlObj.port);
        
        if (!isAvailable) {
            vscode.window.showErrorMessage(`Serwis na ${url} nie odpowiada. Sprawdź czy jest uruchomiony.`);
            return;
        }

        const panel = vscode.window.createWebviewPanel(
            'localhostViewer',
            `🌐 ${url}`,
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                retainContextWhenHidden: true,
            }
        );

        panel.webview.html = getFullBrowserContent(url);

        // Obsługa komunikatów z webview
        panel.webview.onDidReceiveMessage(
            message => {
                switch (message.command) {
                    case 'alert':
                        vscode.window.showInformationMessage(message.text);
                        return;
                    case 'error':
                        vscode.window.showErrorMessage(message.text);
                        return;
                    case 'openExternal':
                        vscode.env.openExternal(vscode.Uri.parse(message.url));
                        return;
                    case 'navigate':
                        panel.webview.html = getFullBrowserContent(message.url);
                        panel.title = `🌐 ${message.url}`;
                        return;
                }
            },
            undefined,
            []
        );

        vscode.window.showInformationMessage(`Otwieram ${url} w pełnej przeglądarce...`);

    } catch (error) {
        vscode.window.showErrorMessage(`Błąd podczas otwierania ${url}: ${error}`);
    }
}

function getNonce() {
	let text = '';
	const possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	for (let i = 0; i < 32; i++) {
		text += possible.charAt(Math.floor(Math.random() * possible.length));
	}
	return text;
}

function getFullBrowserContent(url: string): string {
    const nonce = getNonce();
    return `<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; child-src http://localhost:*; style-src 'unsafe-inline'; script-src 'nonce-${nonce}';">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localhost Viewer - ${url}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1e1e1e;
            color: #ffffff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .browser-header {
            background: #2d2d30;
            padding: 8px 16px;
            border-bottom: 1px solid #3e3e42;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        
        .browser-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn {
            background: #007acc;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn:hover {
            background: #005a9e;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn.danger {
            background: #d73a49;
        }
        
        .btn.danger:hover {
            background: #b31d28;
        }
        
        .url-bar {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .url-input {
            flex: 1;
            background: #3c3c3c;
            border: 1px solid #5a5a5a;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 4px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
        }
        
        .url-input:focus {
            outline: none;
            border-color: #007acc;
        }
        
        .browser-content {
            flex: 1;
            position: relative;
            background: #ffffff;
        }
        
        .content-frame {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007acc;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #d73a49;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .status-bar {
            background: #007acc;
            color: white;
            padding: 4px 16px;
            font-size: 11px;
            text-align: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="browser-header">
        <div class="browser-controls">
            <button class="btn" title="Nawigacja wstecz (niedostępne)" disabled>←</button>
            <button class="btn" title="Nawigacja w przód (niedostępne)" disabled>→</button>
            <button class="btn" onclick="reloadFrame()">🔄</button>
            <button class="btn" onclick="openExternal()">🌍</button>
        </div>
        
        <div class="url-bar">
            <input type="text" class="url-input" id="urlInput" value="${url}" 
                   onkeypress="handleUrlEnter(event)">
            <button class="btn" onclick="navigate()">Przejdź</button>
        </div>
    </div>
    
    <div class="browser-content">
        <iframe 
            src="${url}" 
            id="content-frame"
            class="content-frame"
        ></iframe>
    </div>

    <script nonce="${nonce}">
        const vscode = acquireVsCodeApi();
        const iframe = document.getElementById('content-frame');
        const urlInput = document.getElementById('urlInput');

        function reloadFrame() {
            iframe.src = iframe.src;
        }

        function navigate() {
            const newUrl = urlInput.value.trim();
            if (newUrl) {
                iframe.src = newUrl;
            }
        }

        function handleUrlEnter(event) {
            if (event.key === 'Enter') {
                navigate();
            }
        }

        function openExternal() {
            vscode.postMessage({
                command: 'openExternal',
                url: iframe.src
            });
        }
    </script>
</body>
</html>`;
}

async function checkIfServiceRunning(port: string): Promise<boolean> {
    return new Promise((resolve) => {
        const http = require('http');
        const req = http.request({
            hostname: 'localhost',
            port: port,
            path: '/',
            method: 'HEAD',
            timeout: 3000
        }, (res: any) => {
            resolve(true);
        });
        
        req.on('error', () => {
            resolve(false);
        });
        
        req.on('timeout', () => {
            req.destroy();
            resolve(false);
        });
        
        req.end();
    });
}

export function deactivate() {
    console.log('Localhost Viewer extension deactivated');
} 
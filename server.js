const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = 8000;
const DATA_FILE = 'data.json';
const ADMIN_PASSWORD = 'admin123';

const mimeTypes = {
    '.html': 'text/html',
    '.js': 'text/javascript',
    '.css': 'text/css',
    '.json': 'application/json',
    '.png': 'image/png',
    '.jpg': 'image/jpg',
    '.gif': 'image/gif',
};

const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    let filePath = '.' + parsedUrl.pathname;

    // Handle /save.php emulation
    if (parsedUrl.pathname === '/save.php' && req.method === 'POST') {
        let body = '';
        req.on('data', chunk => {
            body += chunk.toString();
        });
        req.on('end', () => {
            try {
                const input = JSON.parse(body);
                const action = input.action;

                if (action === 'save_all') {
                    if (input.password !== ADMIN_PASSWORD) {
                        res.writeHead(401, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ error: 'Unauthorized' }));
                        return;
                    }
                    fs.writeFileSync(DATA_FILE, JSON.stringify(input.data, null, 2));
                    res.writeHead(200, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ success: true }));
                }
                else if (action === 'vote') {
                    const id = input.id;
                    const score = input.score;

                    if (!id || !score) {
                        res.writeHead(400, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ error: 'Missing parameters' }));
                        return;
                    }

                    let data = [];
                    try {
                        data = JSON.parse(fs.readFileSync(DATA_FILE));
                    } catch (e) {
                        data = [];
                    }

                    let found = false;
                    for (let app of data) {
                        if (app.id === id) {
                            app.rating = app.rating || 0;
                            app.votes = app.votes || 0;
                            const totalScore = (app.rating * app.votes) + score;
                            app.votes++;
                            app.rating = totalScore / app.votes;
                            found = true;
                            break;
                        }
                    }

                    if (found) {
                        fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2));
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true }));
                    } else {
                        res.writeHead(404, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ error: 'App not found' }));
                    }
                } else {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ error: 'Invalid action' }));
                }
            } catch (e) {
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ error: 'Server error: ' + e.message }));
            }
        });
        return;
    }

    // Serve Static Files
    if (filePath === './') {
        filePath = './index.html';
    }

    const extname = String(path.extname(filePath)).toLowerCase();
    const contentType = mimeTypes[extname] || 'application/octet-stream';

    fs.readFile(filePath, (error, content) => {
        if (error) {
            if (error.code == 'ENOENT') {
                res.writeHead(404, { 'Content-Type': 'text/html' });
                res.end('404 Not Found');
            }
            else {
                res.writeHead(500);
                res.end('Sorry, check with the site admin for error: ' + error.code + ' ..\n');
            }
        }
        else {
            res.writeHead(200, { 'Content-Type': contentType });
            res.end(content, 'utf-8');
        }
    });
});

console.log(`Server running at http://localhost:${PORT}/`);
server.listen(PORT);

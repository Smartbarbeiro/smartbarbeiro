<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8" />
        <title>Atualizar Tesora no Hostinger</title>
        <style>
            * { box-sizing: border-box; }

            body {
                color: #111827;
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                line-height: 1.55;
                margin: 0;
                padding: 32px 36px;
            }

            h1 {
                font-size: 20px;
                margin: 0 0 6px;
            }

            h2 {
                border-bottom: 1px solid #d1d5db;
                font-size: 13px;
                margin: 22px 0 10px;
                padding-bottom: 4px;
            }

            h3 {
                font-size: 11px;
                margin: 14px 0 6px;
            }

            p, li {
                margin: 0 0 6px;
            }

            ul, ol {
                margin: 0 0 10px;
                padding-left: 18px;
            }

            .subtitle {
                color: #4b5563;
                font-size: 12px;
                margin: 0 0 18px;
            }

            table {
                border-collapse: collapse;
                margin: 8px 0 12px;
                width: 100%;
            }

            th, td {
                border: 1px solid #d1d5db;
                padding: 6px 8px;
                text-align: left;
                vertical-align: top;
            }

            th {
                background: #f3f4f6;
                font-size: 10px;
                text-transform: uppercase;
            }

            code, .mono {
                background: #f3f4f6;
                font-family: DejaVu Sans Mono, monospace;
                font-size: 10px;
                padding: 1px 4px;
            }

            .box {
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                margin: 8px 0 12px;
                padding: 10px 12px;
            }

            .warn {
                background: #fffbeb;
                border-color: #fcd34d;
            }

            .footer {
                border-top: 1px solid #d1d5db;
                color: #6b7280;
                font-size: 9px;
                margin-top: 24px;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>
        <h1>Como atualizar o site no Hostinger</h1>
        <p class="subtitle">Tesora — Laravel + FTP (sem SSH)</p>

        <p>
            O <code>git push</code> <strong>não</strong> atualiza o Hostinger automaticamente.
            Depois de alterar o código localmente, siga os passos abaixo.
        </p>

        <h2>1. Gerar pacote de deploy (no seu PC)</h2>
        <div class="box mono">
            cd C:\barbearia-app\laravel-cursor<br />
            .\scripts\prepare-hostinger-deploy.ps1
        </div>
        <p>O script executa:</p>
        <ul>
            <li><code>npm run build</code> — compila Vue/CSS/JS em <code>public/build/</code></li>
            <li><code>composer install --no-dev</code> — dependências PHP de produção</li>
            <li>Cria os arquivos em <code>deploy\hostinger\output\</code>:
                <strong>laravel.zip</strong> e <strong>public_html.zip</strong></li>
        </ul>

        <h2>2. Enviar via FileZilla</h2>
        <table>
            <thead>
                <tr>
                    <th>Arquivo local</th>
                    <th>Pasta no servidor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>deploy\hostinger\output\laravel.zip</code></td>
                    <td><code>laravel/</code></td>
                </tr>
                <tr>
                    <td><code>deploy\hostinger\output\public_html.zip</code></td>
                    <td><code>public_html/</code></td>
                </tr>
            </tbody>
        </table>
        <p>No <strong>File Manager</strong> do hPanel, extraia os dois zips (substituir arquivos quando perguntar).</p>
        <div class="box warn">
            <strong>Não sobrescreva</strong> <code>laravel/.env</code> — mantenha o arquivo de produção que já está no servidor.
        </div>

        <h2>3. Limpar cache no servidor</h2>
        <p>Envie para <code>public_html/</code> (se ainda não estiver lá) e abra no navegador:</p>
        <ul>
            <li><code>clear-cache-tesora.php</code> — sempre após deploy</li>
            <li><code>fix-vite-tesora.php</code> — só se assets/CSS/JS não carregarem</li>
        </ul>
        <p>Scripts em: <code>deploy\hostinger\public_html\</code></p>

        <h2>4. Migrations (se houver alteração no banco)</h2>
        <p>Sem terminal, use um <strong>Cron Job</strong> único no hPanel:</p>
        <div class="box mono">
            php ~/laravel/artisan migrate --force
        </div>
        <p>Ou envie temporariamente <code>setup-tesora.php</code> e abra no navegador.</p>

        <h2>O que enviar em cada tipo de mudança</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipo de alteração</th>
                    <th>Enviar</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Só Vue, CSS ou JavaScript</td>
                    <td><code>public_html.zip</code> (contém <code>build/</code>)</td>
                </tr>
                <tr>
                    <td>Só PHP (controllers, models, etc.)</td>
                    <td><code>laravel.zip</code></td>
                </tr>
                <tr>
                    <td>Frontend + backend</td>
                    <td>Os dois zips</td>
                </tr>
                <tr>
                    <td>Variáveis de ambiente</td>
                    <td>Editar <code>laravel/.env</code> no File Manager</td>
                </tr>
                <tr>
                    <td>Nova migration</td>
                    <td><code>laravel.zip</code> + rodar <code>migrate</code></td>
                </tr>
            </tbody>
        </table>

        <h2>5. Checklist pós-deploy</h2>
        <ol>
            <li>Abrir <strong>https://www.tesora.com.br</strong></li>
            <li>Testar login e uma página crítica (ex.: dashboard)</li>
            <li>Remover scripts <code>*-tesora.php</code> de <code>public_html/</code> se reenviou</li>
            <li>Manter <code>APP_DEBUG=false</code> no <code>.env</code> de produção</li>
        </ol>

        <h2>Git (repositório)</h2>
        <div class="box mono">
            git add .<br />
            git commit -m "sua mensagem"<br />
            git push origin version5
        </div>
        <p>Isso salva no GitHub; o deploy no Hostinger continua sendo manual via FTP.</p>

        <h2>Dados de conexão FTP (HostGator)</h2>
        <table>
            <tbody>
                <tr><th>Host</th><td><code>ftp.fulviolopescatto1787174444000.0970020.meusitehostgator.com.br</code></td></tr>
                <tr><th>Usuário</th><td><code>fulvio@fulviolopescatto1787174444000.0970020.meusitehostgator.com.br</code></td></tr>
                <tr><th>Porta</th><td><code>21</code></td></tr>
                <tr><th>PHP no servidor</th><td><strong>8.3</strong> (cPanel → Select PHP Version)</td></tr>
                <tr><th>Banco MySQL</th><td><code>127.0.0.1</code> (no servidor; não use <code>localhost</code> com quebra de linha no .env)</td></tr>
            </tbody>
        </table>

        <h2>Estrutura no servidor</h2>
        <div class="box mono">
            (raiz da conta FTP)<br />
            ├── laravel/ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;← app, vendor, .env (privado)<br />
            └── public_html/ ← index.php, build/, imagens (web)
        </div>

        <p class="footer">
            Gerado em {{ $generatedAt }} — Tesora / branch version5
        </p>
    </body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe — U-Map</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #1e293b;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(99,102,241,0.2);
        }
        .header {
            background: linear-gradient(135deg, #3730a3, #6366f1 50%, #8b5cf6);
            padding: 40px 40px 32px;
            text-align: center;
        }
        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 28px;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }
        .header p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-top: 4px;
        }
        .body {
            padding: 36px 40px;
        }
        .greeting {
            font-size: 16px;
            color: #94a3b8;
            margin-bottom: 16px;
        }
        .greeting strong {
            color: #e2e8f0;
        }
        .text {
            font-size: 15px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .cta-wrap {
            text-align: center;
            margin-bottom: 32px;
        }
        .cta {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white !important;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            padding: 16px 40px;
            border-radius: 14px;
            letter-spacing: 0.3px;
        }
        .divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin: 24px 0;
        }
        .link-fallback {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
            word-break: break-all;
        }
        .link-fallback a {
            color: #818cf8;
        }
        .warning {
            background: rgba(234,179,8,0.08);
            border: 1px solid rgba(234,179,8,0.2);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 13px;
            color: #fde68a;
            margin-bottom: 24px;
        }
        .footer {
            padding: 24px 40px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            font-size: 12px;
            color: #475569;
        }
        .footer a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🗺️</div>
            <h1>U-Map UAC</h1>
            <p>Réinitialisation du mot de passe</p>
        </div>
        <div class="body">
            <p class="greeting">Bonjour <strong>{{ $userName }}</strong>,</p>
            <p class="text">
                Vous avez demandé la réinitialisation de votre mot de passe sur U-Map.
                Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.
                Ce lien est valable pendant <strong>60 minutes</strong>.
            </p>

            <div class="cta-wrap">
                <a href="{{ $resetLink }}" class="cta">
                    🔐 Réinitialiser mon mot de passe
                </a>
            </div>

            <div class="warning">
                ⚠️ Si vous n'avez pas effectué cette demande, ignorez cet e-mail. Votre mot de passe restera inchangé.
            </div>

            <hr class="divider">

            <p class="link-fallback">
                Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br>
                <a href="{{ $resetLink }}">{{ $resetLink }}</a>
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} U-Map UAC &mdash; Université d'Abomey-Calavi<br>
            <a href="mailto:support@umap.bj">support@umap.bj</a>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->subject }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 3px solid #4a90e2;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4a90e2;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 15px;
        }
        .footer {
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4a90e2;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #357abd;
        }
        .notification-type {
            display: inline-block;
            padding: 4px 12px;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .notification-type.payment {
            background-color: #fff3cd;
            color: #856404;
        }
        .notification-type.urgent {
            background-color: #f8d7da;
            color: #721c24;
        }
        .notification-type.general {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Plateforme de Gestion Scolaire</h1>
        </div>

        <div class="content">
            @php
                $typeClass = match($notification->type) {
                    'payment_reminder' => 'payment',
                    'urgent_info' => 'urgent',
                    'general' => 'general',
                    default => 'general'
                };
            @endphp

            <span class="notification-type {{ $typeClass }}">
                @if($notification->type === 'payment_reminder')
                    Rappel de Paiement
                @elseif($notification->type === 'urgent_info')
                    Information Urgente
                @else
                    Notification Générale
                @endif
            </span>

            @if($recipientName)
                <p><strong>Bonjour {{ $recipientName }},</strong></p>
            @else
                <p><strong>Bonjour,</strong></p>
            @endif

            <div>
                {!! nl2br(e($body)) !!}
            </div>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement par la plateforme de gestion scolaire.</p>
            <p>Merci de ne pas répondre à cet email.</p>
            <p>&copy; {{ date('Y') }} Plateforme de Gestion Scolaire. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>


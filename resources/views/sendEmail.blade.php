<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticketing System</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f7f7f7;
            padding: 40px 0;
            display: flex;
            justify-content: center;
        }

        .email-container {
            width: 100%;
            max-width: 700px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .ticket-header {
            background-color: #4CAF50;
            color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .ticket-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .ticket-status {
            text-align: center;
            margin-top: 20px;
        }

        .status {
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            color: #ffffff;
            display: inline-block;
            margin: 10px 0;
        }

        .created {
            background-color: #2196F3;
        }

        .assigned {
            background-color: #FFC107;
        }

        .closed {
            background-color: #f44336;
        }

        .ticket-details {
            margin-top: 30px;
        }

        .ticket-details p {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .ticket-details p:last-child {
            border-bottom: none;
        }

        .ticket-details strong {
            font-weight: bold;
            color: #333;
            min-width: 150px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        .footer img {
            max-width: 100px;
            margin-top: 10px;
        }

        .footer p {
            margin-top: 10px;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 20px;
            }

            .ticket-header h2 {
                font-size: 20px;
            }

            .ticket-details p {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

    <div class="email-wrapper">
        <div class="email-container">
            <!-- Ticket Header -->
            <div class="ticket-header">
                <h2>{{ $ticketData['ticket_status_text'] == 'Closed' ? 'Thank you' : 'Hallo' }} {{ $ticketData['ticket_id'] }}</h2>
                <p><strong>Your ticket has been {{ $ticketData['ticket_status_text'] }}.</strong></p>

            </div>

            <!-- Ticket Status -->
            <div class="ticket-status">
                <div class="status {{ $ticketData['ticket_status'] }}">
                    Ticket Details
                </div>
            </div>

            <!-- Ticket Details -->
            <div class="ticket-details">
                <p><strong>Ticket Subject :</strong> {{ $ticketData['ticket_subject'] }}</p>
                <p><strong>Ticket Description :</strong> {{ $ticketData['ticket_description'] }}</p>
                <p><strong>Priority :</strong> {{ $ticketData['ticket_priority'] }}</p>
                <p><strong>Category :</strong> {{ $ticketData['ticket_category'] }}</p>
                <p><strong>Assigned to :</strong> {{ $ticketData['ticket_assigned_to'] }}</p>
                <p><strong>Note From IT :</strong> <strong> {{ $ticketData['ticket_note'] }}</strong></p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>If you have any questions, please contact our IT support team.</p>
                <img src="https://www.tescoindomaritim.com/images/logo.png" alt="Tesco Indomaritim Logo">
                <p>&copy; 2025 IT Ticketing System - Tesco Indomaritim. All rights reserved.</p>
            </div>
        </div>
    </div>

</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticketing System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .email-container {
            width: 100%;
            max-width: 600px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .ticket-header {
            background-color: #4CAF50;
            color: #ffffff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .ticket-status {
            text-align: center;
            margin-top: 20px;
        }

        .status {
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            color: white;
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
            margin-top: 20px;
        }

        .ticket-details p {
            font-size: 14px;
            margin: 8px 0;
            line-height: 1.6;
        }

        .ticket-details strong {
            color: #333;
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
    </style>
</head>

<body>

    <div class="email-wrapper">
        <div class="email-container">
            <!-- Ticket Header -->
            <div class="ticket-header">
                <h2>Hallo {{ $ticketData['ticket_id'] }}</h2>
                <p>Your ticket has been {{ $ticketData['ticket_status_text'] }}</p>
            </div>

            <!-- Ticket Status -->
            <div class="ticket-status">
                <div class="status {{ $ticketData['ticket_status'] }}">
                    Here are the ticket details
                </div>
            </div>

            <!-- Ticket Details -->
            <div class="ticket-details">
                <p><strong>Ticket Subject:</strong> {{ $ticketData['ticket_subject'] }}</p>
                <p><strong>Ticket Description:</strong> {{ $ticketData['ticket_description'] }}</p>
                <p><strong>Priority:</strong> {{ $ticketData['ticket_priority'] }}</p>
                <p><strong>Assigned to:</strong> {{ $ticketData['ticket_assigned_to'] }}</p>
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
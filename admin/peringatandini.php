<?php
// Simulate database of health alerts
$healthAlerts = [
    ['id' => 1, 'patient' => 'John Doe', 'condition' => 'High Blood Pressure', 'severity' => 'Moderate', 'timestamp' => '2023-10-04 08:30:00'],
    ['id' => 2, 'patient' => 'Jane Smith', 'condition' => 'Elevated Heart Rate', 'severity' => 'Severe', 'timestamp' => '2023-10-04 09:15:00'],
    ['id' => 3, 'patient' => 'Bob Johnson', 'condition' => 'Low Blood Sugar', 'severity' => 'Mild', 'timestamp' => '2023-10-04 10:00:00'],
];

// Function to get severity class for styling
function getSeverityClass($severity) {
    switch (strtolower($severity)) {
        case 'severe':
            return 'severe';
        case 'moderate':
            return 'moderate';
        default:
            return 'mild';
    }
}

// Function to format timestamp
function formatTimestamp($timestamp) {
    $date = new DateTime($timestamp);
    return $date->format('d M Y H:i');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Peringatan Dini Kesehatan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary-color: #1ca883;
        --secondary-color: #f0f9f6;
        --accent-color: #ff6b6b;
        --text-color: #2c3e50;
        --background-color: #ecf0f1;
        --severe-color: #e74c3c;
        --moderate-color: #f39c12;
        --mild-color: #3498db;
    }

    body {
        font-family: 'Poppins', sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 0;
        background-color: var(--background-color);
        color: var(--text-color);
    }

    .container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(28, 168, 131, 0.1);
    }

    header {
        background: linear-gradient(135deg, var(--primary-color), #159f7f);
        color: white;
        text-align: center;
        padding: 1rem;
        border-radius: 20px 20px 0 0;
        margin: -2rem -2rem 2rem -2rem;
    }

    h1 {
        margin: 0;
        font-size: 2rem;
    }

    .alert-container {
        display: grid;
        gap: 1rem;
    }

    .alert {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .alert:hover {
        transform: translateY(-5px);
    }

    .alert-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .patient {
        font-weight: 600;
        font-size: 1.1rem;
    }

    .timestamp {
        font-size: 0.9rem;
        color: #7f8c8d;
    }

    .condition {
        font-size: 1.2rem;
        margin: 0.5rem 0;
    }

    .severity {
        font-weight: 500;
        margin: 0;
    }

    .severe {
        border-left: 5px solid var(--severe-color);
    }

    .moderate {
        border-left: 5px solid var(--moderate-color);
    }

    .mild {
        border-left: 5px solid var(--mild-color);
    }

    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }

        h1 {
            font-size: 1.5rem;
        }
    }
</style>
</head>
<body>


    <div class="container">
        <header>
            <h1>Sistem Peringatan Dini Kesehatan</h1>
        </header>
        <div class="alert-container">
            <?php foreach ($healthAlerts as $alert): ?>
                <div class="alert <?php echo getSeverityClass($alert['severity']); ?>">
                    <div class="alert-header">
                        <span class="patient"><?php echo htmlspecialchars($alert['patient']); ?></span>
                        <span class="timestamp"><?php echo formatTimestamp($alert['timestamp']); ?></span>
                    </div>
                    <div class="alert-body">
                        <p class="condition"><?php echo htmlspecialchars($alert['condition']); ?></p>
                        <p class="severity">Tingkat Keparahan: <?php echo htmlspecialchars($alert['severity']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        // JavaScript for real-time updates (simulated)
        setInterval(function() {
            location.reload();
        }, 60000); // Reload every 60 seconds
    </script>
</body>
</html>
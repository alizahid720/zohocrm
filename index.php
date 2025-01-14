<?php
// Zoho API configuration
$zohoBaseUrl = 'https://www.zohoapis.com/crm/v2';
$clientId = '1000.4ONYVH37DZQXCXTJ71DA5M0OPGNHOK';
$clientSecret = 'be2766a1f41ab4910b236c491dba3199640a93fe6e';
$refreshToken = 'YOUR_REFRESH_TOKEN'; // Replace with your refresh token

// Function to refresh the access token
function refreshAccessToken($clientId, $clientSecret, $refreshToken) {
    $url = "https://accounts.zoho.com/oauth/v2/token";
    $data = [
        'grant_type' => 'refresh_token',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response, true);

    if (isset($result['access_token'])) {
        return $result['access_token'];
    } else {
        die('Failed to refresh access token. Error: ' . htmlspecialchars($result['error'] ?? 'Unknown error'));
    }
}

// Fetch the access token
$accessToken = refreshAccessToken($clientId, $clientSecret, $refreshToken);

// Function to fetch leads
function fetchLeads($search = '') {
    global $zohoBaseUrl, $accessToken;
    $url = $zohoBaseUrl . '/Leads';
    if ($search) {
        $url .= "?criteria=(First_Name:equals:$search) or (Email:equals:$search)";
    }

    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Zoho-oauthtoken $accessToken"
        ]
    ]);

    $response = file_get_contents($url, false, $context);
    return $response ? json_decode($response, true)['data'] : [];
}

// Function to create a lead
function createLead($data) {
    global $zohoBaseUrl, $accessToken;
    $url = $zohoBaseUrl . '/Leads';

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Zoho-oauthtoken $accessToken\r\nContent-Type: application/json",
            'content' => json_encode(['data' => [$data]])
        ]
    ]);

    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"], input[type="email"] {
            padding: 8px;
            margin: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Lead Management</h1>

    <!-- Search Bar -->
    <form method="get" action="">
        <input type="text" name="search" placeholder="Search by Name or Email" value="<?php echo $_GET['search'] ?? ''; ?>">
        <button type="submit">Search</button>
    </form>

    <!-- Display Leads -->
    <?php
    $leads = fetchLeads($_GET['search'] ?? '');
    if ($leads):
    ?>
    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
        </tr>
        <?php foreach ($leads as $lead): ?>
        <tr>
            <td><?php echo htmlspecialchars($lead['Full_Name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($lead['Email'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($lead['Phone'] ?? ''); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p>No leads found.</p>
    <?php endif; ?>

    <!-- Create Lead Form -->
    <h2>Create Lead</h2>
    <form method="post" action="">
        <label for="name">Name</label>
        <input type="text" id="name" name="Name" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="Email" required>

        <label for="phone">Phone</label>
        <input type="text" id="phone" name="Phone" required>

        <button type="submit">Create</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newLead = [
            'Full_Name' => $_POST['Name'],
            'Email' => $_POST['Email'],
            'Phone' => $_POST['Phone']
        ];

        $result = createLead($newLead);

        if ($result && isset($result['data'])) {
            echo '<p>Lead created successfully!</p>';
        } else {
            echo '<p>Failed to create lead.</p>';
        }
    }
    ?>
</div>
</body>
</html>

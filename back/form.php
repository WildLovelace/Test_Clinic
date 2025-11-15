<?php
class FormHandler
{
    private $db;

    public function __construct()
    {
        $this->connectDB();
    }

    private function connectDB()
    {
        $this->db = new mysqli('localhost', 'username', 'password', 'form_db');
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    public function validate($name, $email, $phone)
    {
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (!preg_match('/^\+?[0-9\s\-\(\)]{10,20}$/', $phone)) {
            $errors[] = "Invalid phone format";
        }

        return $errors;
    }

    public function isDuplicate($name, $email, $phone)
    {
        $stmt = $this->db->prepare("SELECT created_at FROM applications 
                                  WHERE name = ? AND email = ? AND phone = ? 
                                  ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("sss", $name, $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastSubmission = strtotime($row['created_at']);
            return (time() - $lastSubmission) < 300;
        }
        return false;
    }

    public function insertApplication($name, $email, $phone)
    {
        $stmt = $this->db->prepare("INSERT INTO applications (name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $phone);
        return $stmt->execute();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new FormHandler();

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    $errors = $handler->validate($name, $email, $phone);

    if (empty($errors) && $handler->isDuplicate($name, $email, $phone)) {
        $errors[] = "You have already submitted this form recently";
    }

    if (empty($errors)) {
        if ($handler->insertApplication($name, $email, $phone)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'errors' => ['Database error']]);
        }
    } else {
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Test form</title>
</head>

<body>
    <div id="message" style="display:none"></div>
    <form id="applicationForm">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="tel" name="phone" placeholder="Phone" required>
        <button type="submit">Отправить</button>
    </form>

    <script src="script.js"></script>
</body>

</html>
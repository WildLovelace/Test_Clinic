<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    class FormHandler
    {
        private $db;

        public function __construct()
        {
            $this->connectDB();
        }

        private function connectDB()
        {
            try {
                $this->db = new mysqli('localhost', 'root', 'root', 'form_db');

                if ($this->db->connect_error) {
                    throw new Exception('Database connection failed');
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
                exit;
            }
        }

        public function validate($name, $email, $phone)
        {
            $errors = [];

            if (empty(trim($name))) {
                $errors[] = "Name is required";
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }

            $phone_cleaned = preg_replace('/[^\d+]/', '', $phone);
            if (empty($phone_cleaned) || strlen($phone_cleaned) < 10) {
                $errors[] = "Invalid phone format";
            }

            return $errors;
        }

        public function isDuplicate($name, $email, $phone)
        {
            try {
                $stmt = $this->db->prepare("SELECT created_at FROM applications 
                                          WHERE name = ? AND email = ? AND phone = ? 
                                          ORDER BY created_at DESC LIMIT 1");
                if (!$stmt) {
                    throw new Exception('Prepare statement failed');
                }

                $stmt->bind_param("sss", $name, $email, $phone);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $lastSubmission = strtotime($row['created_at']);
                    return (time() - $lastSubmission) < 300;
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        }

        public function insertApplication($name, $email, $phone)
        {
            try {
                $stmt = $this->db->prepare("INSERT INTO applications (name, email, phone) VALUES (?, ?, ?)");
                if (!$stmt) {
                    throw new Exception('Prepare statement failed');
                }
                $stmt->bind_param("sss", $name, $email, $phone);
                return $stmt->execute();
            } catch (Exception $e) {
                return false;
            }
        }
    }

    try {
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
                echo json_encode(['success' => false, 'errors' => ['Database insert error']]);
            }
        } else {
            echo json_encode(['success' => false, 'errors' => $errors]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'errors' => ['An unexpected error occurred']]);
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

    <script>
        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            fetch('', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(r => r.json())
                .then(data => {
                    const messageDiv = document.getElementById('message');
                    messageDiv.style.display = 'block';

                    if (data.success) {
                        messageDiv.className = 'success';
                        messageDiv.innerHTML = 'Отправка успешна';
                    } else {
                        messageDiv.className = 'error';
                        messageDiv.innerHTML = data.errors.join('<br>');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const messageDiv = document.getElementById('message');
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'error';
                    messageDiv.innerHTML = 'Произошла ошибка при отправке';
                });
        });
    </script>
</body>

</html>
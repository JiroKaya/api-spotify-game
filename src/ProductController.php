<?php
class ProductController 
{
    public function __construct(private ProductGateway $gateway)
    {
        
    }

    public function processRequest(string $method, ?string $endpoint, ?string $id): void
    {
        if ($id) {
            $this->processResourceRequest($method, $endpoint, $id);
        } else {
            $this->processCollectionRequest($method, $endpoint);
        }
    }

    private function processResourceRequest(string $method, string $endpoint, string $id): void
    {
        $artist = $this->gateway->get($id, $endpoint);

        if (!$artist) {
            http_response_code(404);
            echo json_encode(["message" => "Artist not found"]);
            return;
        }

        switch ($method) {
            case "GET":
                echo json_encode($artist);
                break;
            case "PATCH":
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($artist, $data);
                json_encode([
                    "message" => "Artist $id updated",
                    "rows" => $rows
                ]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);

                echo json_encode([
                    "message" => "Artist $id deleted",
                    "rows" => $rows
                ]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, PATCH, DELETE");
        }

        echo json_encode($artist);
    }

    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;
            case "POST":
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $id = $this->gateway->create($data);

                http_response_code(201);
                json_encode([
                    "message" => "User created",
                    "id" => $id
                ]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST");
        }
    }

    private function getValidationErrors (array $data, bool $is_new = true): array
    {
        $errors = [];

        if ($is_new && empty($data["username"])) {
            $errors[] = "username is required";
        }

        if ($is_new && empty($data["password"])) {
            $errors[] = "password is required";
        }

        if ($is_new && empty($data["created"])) {
            $errors[] = "time of creation is required";
        }

        if ($is_new && empty($data["high_score"])) {
            $errors[] = "highscore is required";
        }

        return $errors;
    }
}
?>
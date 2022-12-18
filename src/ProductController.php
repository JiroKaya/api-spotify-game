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
        $search_object = $this->gateway->get($id, $endpoint);

        if (!$search_object) {
            http_response_code(404);
            echo json_encode(["message" => "Object of search was not found"]);
            return;
        }

        switch ($method) {
            case "GET":
                echo json_encode($search_object);
                break;
            case "PATCH":
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data, $endpoint, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                switch ($endpoint) {
                    case 'users':
                        $rows = $this->gateway->update_user($search_object, $data);
                        echo json_encode([
                            "message" => "User $id updated",
                            "rows" => $rows
                        ]);
                        break;
                    case 'artists':
                        $rows = $this->gateway->update_artist($search_object, $data);
                        echo json_encode([
                            "message" => "Artist $id updated",
                            "rows" => $rows
                        ]);
                        break;
                    default:
                        http_response_code(412);
                        header("Only users and artists allowed");
                        break;
                }
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id, $endpoint);

                echo json_encode([
                    "message" => "$endpoint $id deleted",
                    "rows" => $rows
                ]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, PATCH, DELETE");
        }
    }

    private function processCollectionRequest(string $method, string $endpoint): void
    {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll($endpoint));
                break;
            case "POST":
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data, $endpoint);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                switch ($endpoint) {
                    case 'users':
                        $id = $this->gateway->create_user($data);

                        http_response_code(201);
                        echo json_encode([
                            "message" => "User created",
                            "id" => $id
                        ]);
                        break;
                    case 'artists':
                        $id = $this->gateway->create_artist($data);

                        http_response_code(201);
                        echo json_encode([
                            "message" => "Artist created",
                            "id" => $id
                        ]);
                        break;
                    default:
                        http_response_code(412);
                        header("Only users and artists allowed");
                        break;
                }
            default:
                http_response_code(405);
                header("Allow: GET, POST");
        }
    }

    private function getValidationErrors (array $data, string $endpoint, bool $is_new = true): array
    {
        $errors = [];

        switch ($endpoint) {
            case 'users':
                if ($is_new && empty($data["username"])) {
                    $errors[] = "username is required";
                }
        
                if ($is_new && empty($data["password"])) {
                    $errors[] = "password is required";
                }
        
                if ($is_new && empty($data["created"])) {
                    $errors[] = "time of creation is required";
                }

                if ($is_new && empty($data["modified"])) {
                    $errors[] = "time of modification is required";
                }
        
                if ($is_new && empty($data["high_score"])) {
                    $errors[] = "highscore is required";
                }
                break;
            case 'artists':
                if ($is_new && empty($data["spotify_id"])) {
                    $errors[] = "username is required";
                }
        
                if ($is_new && empty($data["name"])) {
                    $errors[] = "password is required";
                }
        
                if ($is_new && empty($data["created"])) {
                    $errors[] = "time of creation is required";
                }

                if ($is_new && empty($data["modified"])) {
                    $errors[] = "time of modification is required";
                }
        
                if ($is_new && empty($data["pop_score"])) {
                    $errors[] = "highscore is required";
                }
                break;
            default:
                http_response_code(412);
                header("Only users and artists allowed");
                break;
        }

        if ($is_new == false && empty($data["modified"])) {
            $errors[] = "time of modification is required when updating";
        }

        if ($is_new == false && empty($data["created"]) == false) {
            $errors[] = "changing time of creation when updating is not allowed";
        }

        return $errors;
    }
}
?>
<?php
class ProductGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();    
    }

    public function getAll(string $endpoint): array
    {
        switch ($endpoint) {
            case 'users':
                $table = "users";
                break;
            case 'artists':
                $table = "artist_data";
                break;
            default:
                http_response_code(412);
                header("Only users and artists allowed");
                break;
        }
        
        $sql = "SELECT * FROM `$table`";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $data = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            //For Bool values add here this block: $row["specific_column"] = (bool) $row["specific_column"];
            $row["created"] = date("Y-m-d H:i:s",strtotime($row["created"]));
            $row["modified"] = date("Y-m-d H:i:s",strtotime($row["modified"]));
            $data[] = $row;
        }

        return $data;
    }

    public function create_artist(array $data)
    {
        $sql = "INSERT INTO artist_data 
        (spotify_id, name, img_link, genre1, genre2, pop_score, followers, created, modified) 
        VALUES (:spotify_id, :name, :img_link, :genre1, :genre2, :pop_score, :followers, :created, :modified)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":spotify_id", $data["spotify_id"], PDO::PARAM_STR);
        $stmt->bindValue(":img_link", $data["img_link"], PDO::PARAM_STR);
        $stmt->bindValue(":genre1", $data["genre1"], PDO::PARAM_STR);
        $stmt->bindValue(":genre2", $data["genre2"], PDO::PARAM_STR);
        $stmt->bindValue(":pop_score", $data["pop_score"], PDO::PARAM_INT);
        $stmt->bindValue(":followers", $data["followers"], PDO::PARAM_INT);
        $stmt->bindValue(":created", date("Y-m-d H:i:s",strtotime($data["created"])), PDO::PARAM_STR);
        $stmt->bindValue(":modified", date("Y-m-d H:i:s",strtotime($data["modified"])), PDO::PARAM_STR);
        $stmt->bindValue(":high_score", $data["high_score"], PDO::PARAM_INT);

        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function create_user(array $data): string
    {
        $sql = "INSERT INTO users 
        (username, password, created, modified, high_score) 
        VALUES (:username, :password, :created, :modified, :high_score)";

        $stmt = $this->conn->prepare($sql);

        $hashed_password = password_hash($data["password"], PASSWORD_DEFAULT);

        $stmt->bindValue(":username", $data["username"], PDO::PARAM_STR);
        $stmt->bindValue(":password", $hashed_password, PDO::PARAM_STR);
        $stmt->bindValue(":created", date("Y-m-d H:i:s",strtotime($data["created"])), PDO::PARAM_STR);
        $stmt->bindValue(":modified", date("Y-m-d H:i:s",strtotime($data["modified"])), PDO::PARAM_STR);
        $stmt->bindValue(":high_score", $data["high_score"], PDO::PARAM_INT);

        $stmt->execute();

        return $this->conn->lastInsertId();
    }

    public function get(string $id, string $endpoint): array | false
    {
        switch($endpoint) {
            case "users":
                $table = "users";
                break;
            case "artists":
                $table = "artist_data";
                break;
            default:
                throw new Exception("Only users and artists allowed");
                break;
        }
        
        $sql = "SELECT * FROM `$table` WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data !== false) {
            $data["created"] = date("Y-m-d H:i:s",strtotime($data["created"]));
            $data["modified"] = date("Y-m-d H:i:s",strtotime($data["modified"]));
        }

        return $data;
    }

    public function update_user(array $current, array $new): int
    {
        $sql = "UPDATE users 
        SET username=:username, password=:password, modified=:modified, high_score=:high_score 
        WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":username", $new["username"] ?? $current["username"], PDO::PARAM_STR);
        $stmt->bindValue(":password", $new["password"] ?? $current["password"], PDO::PARAM_STR);
        $stmt->bindValue(":modified", date("Y-m-d H:i:s",strtotime($new["modified"])) ?? $current["modified"], PDO::PARAM_STR);
        $stmt->bindValue(":high_score", $new["high_score"] ?? $current["high_score"], PDO::PARAM_STR);
        
        $stmt->bindValue(":id", $current["id"], PDO::PARAM_INT);
        
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function update_artist(array $current, array $new): int
    {
        $sql = "UPDATE artist_data
        SET spotify_id=:spotify_id, name=:name, img_link=:img_link, genre1=:genre1, genre2=:genre2, pop_score=:pop_score, followers=:followers, modified=:modified 
        WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":spotify_id", $new["spotify_id"] ?? $current["spotify_id"], PDO::PARAM_STR);
        $stmt->bindValue(":name", $new["name"] ?? $current["name"], PDO::PARAM_STR);
        $stmt->bindValue(":img_link", $new["img_link"] ?? $current["img_link"], PDO::PARAM_STR);
        $stmt->bindValue(":genre1", $new["genre1"] ?? $current["genre1"], PDO::PARAM_STR);
        $stmt->bindValue(":genre2", $new["genre2"] ?? $current["genre2"], PDO::PARAM_STR);
        $stmt->bindValue(":pop_score", $new["pop_score"] ?? $current["pop_score"], PDO::PARAM_INT);
        $stmt->bindValue(":followers", $new["followers"] ?? $current["followers"], PDO::PARAM_STR);
        $stmt->bindValue(":modified", date("Y-m-d H:i:s",strtotime($new["modified"])) ?? $current["modified"], PDO::PARAM_STR);
        
        $stmt->bindValue(":id", $current["id"], PDO::PARAM_INT);
        
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function delete(string $id, string $endpoint): int
    {
        $sql = "DELETE FROM :table WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        switch ($endpoint) {
            case 'users':
                $stmt->bindValue(":table", "users", PDO::PARAM_STR);
                break;
            case 'artist':
                $stmt->bindValue(":table", "artist_data", PDO::PARAM_STR);
            default:
                http_response_code(412);
                header("Only users and artists allowed");
                break;
        }
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount();
    }
}
?>
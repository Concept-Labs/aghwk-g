<?php
namespace App\Model;

use App\Core\Model;
use PDO;

class Parcel extends Model
{
    protected string $table = 'parcel';

    public function listWhere(string $where,array $params,string $sort,string $dir,int $limit,int $offset): array
    {
        $sql = "SELECT * FROM {$this->table} $where ORDER BY $sort $dir LIMIT :lim OFFSET :off";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v){
            $stmt->bindValue(':'.$k,$v,is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
        $stmt->bindValue(':off',$offset,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countWhere(string $where,array $params): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}

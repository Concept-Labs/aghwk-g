<?php
namespace App\Model;

use App\Core\Model;

class Block extends Model
{
    protected string $table = 'block';
    public function listByParcel(int $parcelId, int $limit = 10): array
    {
        return $this->query(
            "SELECT id,name,acres,crop_category FROM {$this->table}
             WHERE parcel_id = ? LIMIT ?", [$parcelId, $limit]
        );
    }
}

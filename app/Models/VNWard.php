<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;



class VNWard extends Model
{
    use HasFactory;
    protected $table = 'vn_ward';
    protected $fillable = [
        'name',
        'DistrictID'
    ];
    public function district()
    {
        return $this->hasOne(VNDistrict::class, 'id', 'DistrictID');
    }
}

<?php
namespace App\Models;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * Class Contato
 * @package App\Models
 * @version May 30, 2017, 7:52 pm UTC
 */
class Contato extends Model
{
    use SoftDeletes;
    public $table = 'contatos';
    
    protected $dates = ['deleted_at'];
    public $fillable = [
        'nome',
        'email',
        'mensagem'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'nome' => 'string',
        'email' => 'string',
        'mensagem' => 'string'
    ];
    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'nome' => 'required',
        'email' => 'required|email',
        'mensagem' => 'exit'
    ];
    
}
<?php<?php



namespace App\Models;namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\BelongsTo;



class UserMedication extends Modelclass UserMedication extends Model

{{

    use HasFactory;    use HasFactory;



    protected $fillable = [    protected $fillable = [

        'user_id',        'user_id',

        'rxcui',        'rxcui',

        'drug_name',        'drug_name',

        'base_names',        'base_names',

        'dose_form_group_names',        'dose_form_group_names',

    ];    ];



    protected function casts(): array    protected function casts(): array

    {    {

        return [        return [

            'base_names' => 'array',            'base_names' => 'array',

            'dose_form_group_names' => 'array',            'dose_form_group_names' => 'array',

        ];        ];

    }    }



    public function user(): BelongsTo    public function user(): BelongsTo

    {    {

        return $this->belongsTo(User::class);        return $this->belongsTo(User::class);

    }    }

}}


<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProjectA00Form extends Model
{
    protected $guarded=[];
    protected function casts(): array { return [
        'document_date'=>'date','request_received_date'=>'date','due_part_list'=>'date','due_umh'=>'date',
        'due_new_part_price'=>'date','due_costing'=>'date','due_submit_quotation'=>'date','pp1_date'=>'date',
        'pp2_date'=>'date','pp3_date'=>'date','sop_mp_date'=>'date','spot_order'=>'boolean','sop_mp_tba'=>'boolean',
        'customer_events'=>'array',
        'issued_at'=>'datetime',
    ]; }

    public function resolvedCustomerEvents(): array
    {
        $events = collect($this->customer_events ?? [])
            ->map(fn ($event) => [
                'name' => trim((string) ($event['name'] ?? '')),
                'date' => filled($event['date'] ?? null) ? (string) $event['date'] : null,
                'tba' => (bool) ($event['tba'] ?? false),
            ])
            ->filter(fn ($event) => $event['name'] !== '')
            ->values()
            ->all();

        if ($events !== []) {
            return $events;
        }

        return collect([
            ['name' => 'PP1', 'date' => $this->pp1_date?->format('Y-m-d'), 'tba' => false],
            ['name' => 'PP2', 'date' => $this->pp2_date?->format('Y-m-d'), 'tba' => false],
            ['name' => 'PP3', 'date' => $this->pp3_date?->format('Y-m-d'), 'tba' => false],
            ['name' => 'SOP/MP', 'date' => $this->sop_mp_date?->format('Y-m-d'), 'tba' => (bool) $this->sop_mp_tba],
        ])->filter(fn ($event) => $event['date'] || $event['tba'])->values()->all();
    }

    public function resolvedMassProductionEvent(): ?array
    {
        $events = collect($this->resolvedCustomerEvents());

        return $events->first(fn ($event) => preg_match('/\b(SOP|MP|MASS\s*PRO)\b/i', $event['name']) === 1)
            ?? $events->last();
    }

    public function resolvedMassProductionDate(): ?\Carbon\Carbon
    {
        $event = $this->resolvedMassProductionEvent();

        return filled($event['date'] ?? null) ? \Carbon\Carbon::parse($event['date']) : null;
    }

    public function formattedCustomerName(): string
    {
        $name = trim((string) $this->customer);

        if (preg_match('/^(.+?),\s*PT$/iu', $name, $matches) === 1) {
            return 'PT '.trim($matches[1]);
        }

        return $name;
    }

    public function resolvedSignaturePath(string $role): ?string
    {
        [$types,$nameColumn,$legacyColumn]=match($role){
            'approved'=>[['president_director','director'],'approved_by','approved_signature_path'],
            'acknowledged'=>[['director','div_marketing'],'acknowledged_by','acknowledged_signature_path'],
            'prepared'=>[['marketing'],'prepared_by','prepared_signature_path'],
            default=>[null,null,null],
        };
        if(!$types)return null;
        $master=$this->resolvedSignerPic($types,$nameColumn)?->signature_path;
        return $master?:$this->{$legacyColumn};
    }

    public function resolvedSignerRoleLabel(string $role): string
    {
        [$types,$nameColumn,$fallback]=match($role){
            'approved'=>[['president_director','director'],'approved_by','Direktur Utama'],
            'acknowledged'=>[['director','div_marketing'],'acknowledged_by','Direktur'],
            'prepared'=>[['marketing'],'prepared_by','Marketing'],
            default=>[null,null,'-'],
        };

        if (!$types) return $fallback;

        $type=$this->resolvedSignerPic($types,$nameColumn)?->type;

        return match($type){
            'president_director'=>'Direktur Utama',
            'director'=>'Direktur',
            'div_marketing'=>'Div. Marketing',
            'marketing'=>'Marketing',
            'engineering'=>'Engineering',
            default=>$fallback,
        };
    }

    private function resolvedSignerPic(array $types, string $nameColumn): ?Pic
    {
        $normalized=preg_replace('/[^\pL\pN]+/u','',mb_strtolower(trim((string)$this->{$nameColumn})));

        return $normalized==='' ? null : Pic::query()->whereIn('type',$types)->get()
            ->first(fn($pic)=>preg_replace('/[^\pL\pN]+/u','',mb_strtolower(trim($pic->name)))===$normalized);
    }
    public function project(){return $this->belongsTo(DocumentProject::class,'document_project_id');}
    public function projectRevision(){return $this->belongsTo(DocumentRevision::class,'document_revision_id');}
    public function items(){return $this->hasMany(ProjectA00Item::class)->orderBy('line_number');}
    public function costingGroup(){return $this->hasOne(CostingGroup::class,'project_a00_form_id');}
    public function creator(){return $this->belongsTo(User::class,'created_by');}
}

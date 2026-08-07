<?php
namespace App\Services\Costing;
use App\Models\CostingGroup;
use App\Models\User;
use App\Notifications\CostingGroupChanged;
class CostingGroupNotificationService {
 public function notify(CostingGroup $group,string $event,string $message,array $extraNames=[]): int {
  $group->loadMissing(['a00Form','activeItems']);
  $names=collect([$group->pic_engineering,$group->pic_marketing])->merge($group->activeItems->flatMap(fn($item)=>[$item->effectivePicEngineering(),$item->effectivePicMarketing()]))->merge($extraNames)->filter()->map(fn($name)=>mb_strtolower(trim((string)$name)))->unique();
  if($names->isEmpty()) return 0;
  $users=User::query()->where(function($query) use($names){foreach($names as $name)$query->orWhereRaw('LOWER(TRIM(name)) = ?',[$name]);})->get()->unique('id');
  $payload=['event'=>$event,'title'=>'Pembaruan Bulky COGM','message'=>$message,'costing_group_id'=>$group->id,'a00_number'=>$group->a00Form?->document_number,'url'=>route('project',absolute:false)];
  $users->each->notify(new CostingGroupChanged($payload)); return $users->count();
 }
}

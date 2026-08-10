<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $creatorId=DB::table('users')->where('role','admin')->value('id') ?: DB::table('users')->value('id');
        $revisions=DB::table('document_revisions as r')->join('document_projects as p','p.id','=','r.document_project_id')
            ->leftJoin('products as product','product.id','=','p.product_id')
            ->where('r.change_remark','Dokumen awal diterima (baseline V0).')
            ->whereNotExists(fn($query)=>$query->selectRaw('1')->from('project_workflow_tasks as task')->whereColumn('task.document_revision_id','r.id'))
            ->select('r.*','p.customer','p.model','p.part_number','p.part_name','product.name as business_category')->get();
        foreach($revisions as $revision){
            DB::table('project_workflow_tasks')->insert([
                'document_project_id'=>$revision->document_project_id,'document_revision_id'=>$revision->id,
                'stage'=>'breakdown','assigned_role'=>'admin_costing','status'=>'pending','available_at'=>now(),
                'metadata'=>json_encode(['source'=>'new_project_draft','without_a00'=>true]),'created_at'=>now(),'updated_at'=>now(),
            ]);
            if(!DB::table('document_control_registrations')->where('document_revision_id',$revision->id)->exists()){
                DB::table('document_control_registrations')->insert([
                    'document_project_id'=>$revision->document_project_id,'document_revision_id'=>$revision->id,
                    'customer'=>$revision->customer,'project'=>$revision->model,'part_number'=>$revision->part_number,
                    'part_name'=>$revision->part_name,'revision_number'=>'V'.$revision->version_number,'a00'=>'belum_ada',
                    'drawing_status'=>'Belum Diproses','business_category'=>$revision->business_category,
                    'created_by'=>$creatorId,'row_order'=>((int)DB::table('document_control_registrations')->max('row_order'))+1000,
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        }
    }
    public function down(): void {}
};

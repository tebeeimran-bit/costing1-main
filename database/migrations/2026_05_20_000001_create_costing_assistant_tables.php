<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_topics', function (Blueprint $table) {
            $table->id();
            $table->string('menu')->default('general');
            $table->string('title');
            $table->text('content');
            $table->string('role')->nullable();
            $table->json('keywords')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('assistant_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('condition_type')->default('always');
            $table->json('condition_payload')->nullable();
            $table->string('severity')->default('info');
            $table->text('message');
            $table->string('action_label')->nullable();
            $table->string('action_url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('assistant_file_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('excel');
            $table->string('name');
            $table->json('required_columns')->nullable();
            $table->json('optional_columns')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('assistant_topics')->insert([
            [
                'menu' => 'form',
                'title' => 'Form Costing belum bisa submit',
                'content' => 'Pastikan partlist sudah diimport, cycle time terisi, rate kurs tersedia, tidak ada unpriced parts aktif, lalu generate COGM sebelum submit approval.',
                'role' => null,
                'keywords' => json_encode(['submit', 'costing', 'form', 'cogm', 'approval']),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'menu' => 'database',
                'title' => 'Import master data parts',
                'content' => 'Gunakan template Excel database parts. Kolom penting yang biasa dicek adalah material_code, material_description, base_uom, price, currency, dan price_update.',
                'role' => null,
                'keywords' => json_encode(['import', 'excel', 'parts', 'material', 'database']),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'menu' => 'approval',
                'title' => 'Alur approval COGM',
                'content' => 'Costing mengirim submission ke coordinator. Coordinator dapat approve, reject dengan catatan, atau send to marketing setelah approved.',
                'role' => null,
                'keywords' => json_encode(['approval', 'coordinator', 'marketing', 'reject', 'approve']),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'menu' => 'file',
                'title' => 'Validasi file lokal',
                'content' => 'Assistant dapat mengecek Excel secara lokal untuk membaca sheet, header, jumlah row, kolom wajib, dan potensi data kosong. PDF dicek metadata dan format dasarnya tanpa mengirim file ke layanan luar.',
                'role' => null,
                'keywords' => json_encode(['upload', 'file', 'pdf', 'xlsx', 'excel', 'validasi']),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('assistant_rules')->insert([
            [
                'code' => 'unresolved_unpriced_parts',
                'title' => 'Unpriced parts aktif',
                'condition_type' => 'unresolved_unpriced_gt',
                'condition_payload' => json_encode(['count' => 0]),
                'severity' => 'warning',
                'message' => 'Masih ada part tanpa harga. Costing sebaiknya belum disubmit sampai unpriced parts diselesaikan.',
                'action_label' => 'Lihat Unpriced Parts',
                'action_url' => '/database/unpriced-parts',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'waiting_coordinator_approval',
                'title' => 'Menunggu approval coordinator',
                'condition_type' => 'waiting_approval_gt',
                'condition_payload' => json_encode(['count' => 0]),
                'severity' => 'info',
                'message' => 'Ada COGM yang sedang menunggu keputusan coordinator. Cek inbox approval sebelum mengirim ke marketing.',
                'action_label' => 'Buka Project',
                'action_url' => '/project',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'missing_current_rate',
                'title' => 'Rate kurs bulan berjalan belum ada',
                'condition_type' => 'missing_exchange_rate_current_month',
                'condition_payload' => null,
                'severity' => 'warning',
                'message' => 'Rate kurs bulan berjalan belum ditemukan. Input rate kurs sebelum hitung atau review costing periode ini.',
                'action_label' => 'Input Rate Kurs',
                'action_url' => '/database/rate-kurs',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'form_page_guide',
                'title' => 'Panduan halaman form costing',
                'condition_type' => 'route_is',
                'condition_payload' => json_encode(['patterns' => ['form', 'costing.*']]),
                'severity' => 'info',
                'message' => 'Urutan kerja yang disarankan: lengkapi project info, import partlist, import cycle time/UMH, cek unpriced parts, generate COGM, lalu submit approval.',
                'action_label' => 'Buka Project',
                'action_url' => '/project',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('assistant_file_templates')->insert([
            [
                'type' => 'excel',
                'name' => 'Database Parts Excel',
                'required_columns' => json_encode(['material_code', 'material_description', 'base_uom', 'price', 'currency']),
                'optional_columns' => json_encode(['plant', 'material_type', 'material_group', 'purchase_unit', 'moq', 'maker', 'price_update']),
                'validation_rules' => json_encode(['unique_by' => 'material_code', 'max_empty_required_cells' => 0]),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'excel',
                'name' => 'Partlist / Costing Excel',
                'required_columns' => json_encode(['part_no', 'part_name', 'qty']),
                'optional_columns' => json_encode(['material_code', 'uom', 'price', 'supplier']),
                'validation_rules' => json_encode(['unique_by' => 'part_no', 'max_empty_required_cells' => 0, 'workflow' => 'create_new_project', 'mapping' => ['customer' => 'customer', 'model' => 'model', 'business_category' => 'business_category', 'part_no' => 'part_no', 'part_name' => 'part_name', 'qty' => 'qty', 'pic_engineering' => 'pic_engineering', 'pic_marketing' => 'pic_marketing', 'received_date' => 'received_date']]),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'pdf',
                'name' => 'Dokumen Engineering PDF',
                'required_columns' => json_encode([]),
                'optional_columns' => json_encode([]),
                'validation_rules' => json_encode(['allowed_extension' => 'pdf', 'max_size_mb' => 20]),
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_file_templates');
        Schema::dropIfExists('assistant_rules');
        Schema::dropIfExists('assistant_topics');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate data from pages to contents
        DB::statement("
            INSERT INTO contents (
                id,
                tenant_id,
                url,
                content_type,
                source,
                title,
                keyword,
                language,
                status,
                created_at,
                updated_at
            )
            SELECT
                id,
                tenant_id,
                url_path,
                'article',
                'manual',
                NULL,
                keyword,
                language,
                'active',
                created_at,
                updated_at
            FROM pages
            WHERE id NOT IN (SELECT id FROM contents)
        ");

        // Update content_generations to use content_id column name
        // (The foreign key relationship will work because IDs are preserved)

        // Associate existing prompts with content-generation tool
        $contentGenToolId = DB::table('tools')->where('code', 'content-generation')->value('id');

        if ($contentGenToolId) {
            DB::table('prompts')
                ->whereNull('tool_id')
                ->update(['tool_id' => $contentGenToolId]);
        }

        echo "\n✅ Data migration completed:\n";
        $pagesCount = DB::table('pages')->count();
        $contentsCount = DB::table('contents')->count();
        $promptsUpdated = DB::table('prompts')->where('tool_id', $contentGenToolId)->count();

        echo "   - Pages migrated: {$pagesCount} → Contents: {$contentsCount}\n";
        echo "   - Prompts associated with content-generation tool: {$promptsUpdated}\n";
        echo "   - content_generations table still references same IDs\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated data (keeping pages table intact for rollback)
        DB::statement("
            DELETE FROM contents
            WHERE source = 'manual'
            AND id IN (SELECT id FROM pages)
        ");

        // Reset prompts tool_id
        DB::table('prompts')->update(['tool_id' => null]);

        echo "\n✅ Data migration reversed\n";
    }
};

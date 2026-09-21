<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ersetzt "<br />"-Fragmente aus früherem nl2br() in Nutzertext durch "\n"; Rohtext wird per {{ }} +
 * white-space: pre-line ausgegeben, weiteres HTML bleibt unangetastet. Läuft chunkweise in PHP
 * (JSON-Spalte, verschlüsselte Chat-Nachrichten) und ist idempotent.
 */
return new class extends Migration
{
    private const BR_PATTERN = '/<br\s*\/?\s*>/i';

    public function up(): void
    {
        $this->replaceInPlainColumn('comments', 'text');
        $this->replaceInPlainColumn('projects', 'description');
        $this->replaceInPlainColumn('timelines', 'description');
        $this->replaceInPlainColumn('preset_timeline_times', 'description');
        $this->replaceInJsonTextKey('project_component_values', 'data');
        $this->replaceInEncryptedChatMessages();
    }

    public function down(): void
    {
        // Rohtext ist das Zielformat; kein Zurückschreiben von "<br />".
    }

    private function replaceInPlainColumn(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select(['id', $column])
            ->where($column, 'like', '%<br%')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $column): void {
                foreach ($rows as $row) {
                    $replaced = $this->stripBr((string) $row->{$column});
                    if ($replaced !== (string) $row->{$column}) {
                        DB::table($table)->where('id', $row->id)->update([$column => $replaced]);
                    }
                }
            });
    }

    /**
     * project_component_values.data ist JSON ({"text": "..."}). Nur der text-Key wird angefasst.
     */
    private function replaceInJsonTextKey(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select(['id', $column])
            ->where($column, 'like', '%<br%')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $column): void {
                foreach ($rows as $row) {
                    $data = json_decode((string) $row->{$column}, true);
                    if (!is_array($data) || !isset($data['text']) || !is_string($data['text'])) {
                        continue;
                    }

                    $replaced = $this->stripBr($data['text']);
                    if ($replaced === $data['text']) {
                        continue;
                    }

                    $data['text'] = $replaced;
                    DB::table($table)->where('id', $row->id)->update([
                        $column => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }

    /**
     * chat_messages.message liegt per Crypt::encryptString verschlüsselt vor (Altbestand ggf. Klartext),
     * ein LIKE-Vorfilter ist also nicht möglich: alle Zeilen werden entschlüsselt geprüft.
     */
    private function replaceInEncryptedChatMessages(): void
    {
        if (!Schema::hasTable('chat_messages') || !Schema::hasColumn('chat_messages', 'message')) {
            return;
        }

        DB::table('chat_messages')
            ->select(['id', 'message'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $stored = (string) $row->message;
                    $encrypted = true;
                    try {
                        $plain = Crypt::decryptString($stored);
                    } catch (\Throwable) {
                        $plain = $stored;
                        $encrypted = false;
                    }

                    $replaced = $this->stripBr($plain);
                    if ($replaced === $plain) {
                        continue;
                    }

                    DB::table('chat_messages')->where('id', $row->id)->update([
                        'message' => $encrypted ? Crypt::encryptString($replaced) : $replaced,
                    ]);
                }
            });
    }

    private function stripBr(string $text): string
    {
        // nl2br() lässt den ursprünglichen Umbruch stehen ("\n<br />" bzw. "<br />\n"): erst die
        // Kombination auf einen Umbruch reduzieren, dann alleinstehende Fragmente ersetzen.
        $text = preg_replace('/(\r\n|\r|\n)?<br\s*\/?\s*>(\r\n|\r|\n)?/i', "\n", $text) ?? $text;

        return preg_replace(self::BR_PATTERN, "\n", $text) ?? $text;
    }
};

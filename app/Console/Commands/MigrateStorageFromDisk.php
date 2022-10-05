<?php

namespace App\Console\Commands;

use App\Helpers\UploadHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class MigrateStorageFromDisk extends Command
{
    /**
     * The name and signature of the console command.
     *  array of each table name and the path field in the table colon separated e.x attachment:path chunk_uploads:path
     * @var string
     */
    protected $signature = 'storage:migrate {tables_and_path_fields*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload files from disk remotely';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $inputValues = $this->argument('tables_and_path_fields');
        $tables = $this->extractTablesAndFields($inputValues);
        $this->migrateTables($tables);
    }

    /**
     * @param $inputValues
     * @return array e.x {name => name , field => path}
     */
    private function extractTablesAndFields($inputValues): array
    {
        $tables = [];
        foreach ($inputValues as $table) {
            $input = explode(':', $table);
            $tables [] = [
                'name' => $input[0],
                'field' => $input[1]
            ];
        }
        return $tables;
    }

    /**
     * @param string $message
     * @return void
     */
    private function printLine(string $message, string $color, string $styleName): void
    {
        $style = new OutputFormatterStyle($color);
        $this->output->getFormatter()->setStyle($styleName, $style);
        $this->line($message, $styleName, null);
    }

    /**
     * @param array $tables
     * @return void
     */
    public function migrateTables(array $tables): void
    {
        $totalNumber = count($tables);
        $this->info(json_encode($tables));
        $this->info($totalNumber);
        $currentTableNumber = 1;
        foreach ($tables as $table) {
            $this->warn("Currently Processing Table: {$table['name']}");
            $this->printLine("progress: {$currentTableNumber} out of: {$totalNumber} tables...", 'cyan', 'progress');
            $rows = DB::table("{$table['name']}")->get();
            $totalNumberOfRows = count($rows);
            $bulkUpdateRawQuery = "update {$table['name']} set {$table['field']} = case";
            foreach ($rows as $index => $row) {
                $this->printLine("Upload progress in {$table['name']} table: {$index} out of: {$totalNumberOfRows} files uploaded...", 'default', 'upload_progress');
                $filePath = explode($row->path, '/');
                $type = $filePath[0];
                array_shift($filePath);
                $file = public_path($row->path);
                $fileName = implode('/', $filePath);
                UploadHelper::upload($file, $type, $fileName);
                $bulkUpdateRawQuery .= "\n when id={$row->id} then 'newValuesssss'";
            }
            $bulkUpdateRawQuery .= "\n else {$table['field']} end";
            $this->info("Bulk updating {$table['name']} with new {$table['field']} values....");
            DB::update($bulkUpdateRawQuery);
            $this->info("Done Executing bulk update on {$table['name']} table!");
            $currentTableNumber++;
        }
        $rows = DB::table("attachments")->get();
        $this->info(json_encode($rows));
    }
}

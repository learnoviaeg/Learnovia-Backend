<?php

namespace App\Console\Commands;

use App\Constants\StorageTypes;
use App\Helpers\UploadHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use function count;


class MigrateStorageFromDisk extends Command
{

    /**
     * The name and signature of the console command.
     *  array of each table name and the path field in the table colon separated e.x attachment:path chunk_uploads:path
     * @var string
     */
    protected $signature = 'storage:migrate
                            {table : The table name}
                            {-P|--path= : The file path field in the table}
                            {-N|--name= : The file name field in the table}
                            {-T|--type= : The file type filed in the table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loop over a table to migrate the data to azure storage';


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
        $noOfSuccessUploads = 0;
        $table = $this->argument('table');
        $pathColumn = $this->option('path');
        $nameColumn = $this->option('name');
        $typeColumn = $this->option('type');

        $this->warn("Currently Processing Table: {$table}");
        $rows = DB::table("{$table}")->get();
        $totalNumberOfRows = count($rows);
        $bulkUpdateRawQuery = "UPDATE {$table} SET {$pathColumn} = CASE";
        foreach ($rows as $index => $row) {
            $rowAlreadyMigrated = str_contains($row->{$pathColumn}, env('AZURE_STORAGE_URL'));
            if ($rowAlreadyMigrated) {
                $this->info("Row {$index} of {$totalNumberOfRows} already migrated");
                continue;
            }
            $this->printLine("Upload progress in {$table} table: {$index} out of: {$totalNumberOfRows} files uploaded...", 'default', 'upload_progress');
            $this->printLine("Currently Processing File: {$row->{$pathColumn}}", 'default', 'file_name');
            $file = "public/storage/{$row->{$pathColumn}}";
            $type = strtolower($row->{$typeColumn});
            $type = $type === 'assigment' ? StorageTypes::ASSIGNMENT : $type;
            $fileName = $row->{$nameColumn};
            if(!file_exists($file))
            {
                continue;
            }
                try {
                $imgUrl = UploadHelper::upload($file, $type, $fileName);

            } catch (\Exception $e) {
                $this->error("Error uploading file: {$row->{$pathColumn}}");
                $this->error($e->getMessage());
                break;
            }

            $bulkUpdateRawQuery .= "\n WHEN id={$row->id} then '{$imgUrl}'";
            $noOfSuccessUploads += 1;
        }
        $this->info("Bulk updating {$table} with new {$pathColumn} values....");
        if ($noOfSuccessUploads > 0) {
            $bulkUpdateRawQuery .= "\n ELSE {$pathColumn} END";
            DB::update($bulkUpdateRawQuery);
        }
        $this->info("Done migrating {$table} table! {$noOfSuccessUploads} records migrated successfully");
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

}

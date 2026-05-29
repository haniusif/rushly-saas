<?php

namespace App\Console\Commands;

use App\Models\Categorys;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Mail;
class DatabaseAutoBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:autobackup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mysqlHostName      = env('DB_HOST');
        $mysqlUserName      = env('DB_USERNAME');
        $mysqlPassword      = env('DB_PASSWORD');
        $DbName             = env('DB_DATABASE');

        $connect = new \PDO(
            "mysql:host=$mysqlHostName;dbname=$DbName;charset=utf8",
            "$mysqlUserName",
            "$mysqlPassword",
            array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
        );
        // Stream rows from the server one at a time instead of buffering the
        // whole result set into PHP memory (the cause of the OOM fatals).
        $connect->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $statement = $connect->query("SHOW TABLES");
        $tables = $statement->fetchAll(\PDO::FETCH_COLUMN);
        $statement->closeCursor();

        $file_name = 'database_backup_on_' . date('y-m-d-his') . '.sql';
        $backup_dir = storage_path('app/backups');
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        $file_path = $backup_dir . '/' . $file_name;
        $handle = fopen($file_path, 'w');

        foreach ($tables as $table) {
            $createStatement = $connect->query("SHOW CREATE TABLE `$table`");
            $create = $createStatement->fetch(\PDO::FETCH_ASSOC);
            $createStatement->closeCursor();
            fwrite($handle, "\n\n" . $create["Create Table"] . ";\n\n");

            $rows = $connect->query("SELECT * FROM `$table`");
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $columns = array_keys($row);
                $values = array_map(function ($value) use ($connect) {
                    return is_null($value) ? 'NULL' : $connect->quote($value);
                }, array_values($row));

                fwrite(
                    $handle,
                    "\nINSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n"
                );
            }
            $rows->closeCursor();
        }
        fclose($handle);

        Mail::send('backend.merchant.invoice.invoice_mail_pdf', [], function ($message) use ($file_path, $file_name) {
            $message->to(settings()->email, settings()->name)
                    ->subject('Database Backup - ' . date('Y-m-d h:i:s'))
                    ->from(settings()->email, settings()->name)
                    ->attach($file_path, ['as' => $file_name, 'mime' => 'application/sql']);
        });

        @unlink($file_path);
    }
}

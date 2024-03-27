<?php

use Antonrom\ModelChangesHistory\Models\Change;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected string $tableName;

    public function __construct()
    {
        $this->connection = config('model_changes_history.stores.database.connection', null);
        $this->tableName  = config('model_changes_history.stores.database.table', 'model_changes_history');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type');
            $table->json('before_changes')->nullable();
            $table->json('after_changes')->nullable();
            $table->json('changes')->nullable();
            $table->enum('change_type', Change::getTypes());
            $table->string('changer_type')->nullable();
            $table->unsignedBigInteger('changer_id')->nullable();
            $table->json('stack_trace')->nullable();
            $table->timestamp(Model::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};

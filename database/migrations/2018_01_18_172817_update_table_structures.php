<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableStructures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            //$table->dropForeign('oauth_refresh_tokens_access_token_id_foreign');
        });
        Schema::drop('oauth_refresh_tokens');
        Schema::table('oauth_access_token_scopes', function (Blueprint $table) {
            $table->dropForeign('oauth_access_token_scopes_scope_id_foreign');
            $table->dropForeign('oauth_access_token_scopes_access_token_id_foreign');
        });
        Schema::drop('oauth_access_token_scopes');

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
         //   $table->dropForeign('oauth_access_tokens_session_id_foreign');
        });
        Schema::drop('oauth_access_tokens');
        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token')->unique();
            $table->string('client_id');
            // use a string for the user identifier
            $table->string('user_id')->nullable();
            $table->timestamp('expires_at')->useCurrent();
            $table->timestamps();
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });

        Schema::create('oauth_refresh_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token')->unique();
            $table->integer('access_token_id')->unsigned();
            $table->timestamp('expires_at')->useCurrent();
            $table->timestamps();
            $table->foreign('access_token_id')->references('id')->on('oauth_access_tokens')->onDelete('cascade');
        });

        Schema::create('oauth_access_token_scopes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('access_token_id')->unsigned();
            $table->string('scope_id');
            $table->index('access_token_id');
            $table->index('scope_id');
            $table->foreign('access_token_id')
                ->references('id')->on('oauth_access_tokens')
                ->onDelete('cascade');
            $table->foreign('scope_id')
                ->references('id')->on('oauth_scopes')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // TODO: There isn't really a good way to revert this actually
    }
}

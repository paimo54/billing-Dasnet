<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create FreeRADIUS standard tables for authentication, authorization, and accounting
     */
    public function up(): void
    {
        // radcheck - Authentication table
        Schema::create('radcheck', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64)->index();
            $table->string('attribute', 64);
            $table->string('op', 2)->default('==');
            $table->string('value', 253);

            $table->index(['username', 'attribute']);
        });

        // radreply - Authorization reply attributes
        Schema::create('radreply', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64)->index();
            $table->string('attribute', 64);
            $table->string('op', 2)->default('=');
            $table->string('value', 253);

            $table->index(['username', 'attribute']);
        });

        // radgroupcheck - Group authentication
        Schema::create('radgroupcheck', function (Blueprint $table) {
            $table->id();
            $table->string('groupname', 64)->index();
            $table->string('attribute', 64);
            $table->string('op', 2)->default('==');
            $table->string('value', 253);

            $table->index(['groupname', 'attribute']);
        });

        // radgroupreply - Group authorization reply
        Schema::create('radgroupreply', function (Blueprint $table) {
            $table->id();
            $table->string('groupname', 64)->index();
            $table->string('attribute', 64);
            $table->string('op', 2)->default('=');
            $table->string('value', 253);

            $table->index(['groupname', 'attribute']);
        });

        // radusergroup - User to group mapping
        Schema::create('radusergroup', function (Blueprint $table) {
            $table->string('username', 64)->index();
            $table->string('groupname', 64);
            $table->integer('priority')->default(1);

            $table->index(['username', 'priority']);
        });

        // radacct - Accounting table (session tracking)
        Schema::create('radacct', function (Blueprint $table) {
            $table->bigIncrements('radacctid');
            $table->string('acctsessionid', 64)->index();
            $table->string('acctuniqueid', 32)->unique();
            $table->string('username', 64)->index();
            $table->string('groupname', 64)->nullable();
            $table->string('realm', 64)->nullable();
            $table->string('nasipaddress', 15)->index();
            $table->string('nasportid', 32)->nullable();
            $table->string('nasporttype', 32)->nullable();
            $table->dateTime('acctstarttime')->nullable()->index();
            $table->dateTime('acctupdatetime')->nullable();
            $table->dateTime('acctstoptime')->nullable()->index();
            $table->integer('acctsessiontime')->unsigned()->nullable();
            $table->string('acctauthentic', 32)->nullable();
            $table->string('connectinfo_start', 50)->nullable();
            $table->string('connectinfo_stop', 50)->nullable();
            $table->bigInteger('acctinputoctets')->unsigned()->nullable();
            $table->bigInteger('acctoutputoctets')->unsigned()->nullable();
            $table->string('calledstationid', 50)->nullable();
            $table->string('callingstationid', 50)->nullable();
            $table->string('acctterminatecause', 32)->nullable();
            $table->string('servicetype', 32)->nullable();
            $table->string('framedprotocol', 32)->nullable();
            $table->string('framedipaddress', 15)->nullable();
            $table->string('framedipv6address', 45)->nullable();
            $table->string('framedipv6prefix', 45)->nullable();
            $table->string('framedinterfaceid', 44)->nullable();
            $table->string('delegatedipv6prefix', 45)->nullable();

            $table->index(['username', 'acctstarttime']);
            $table->index(['acctstoptime']);
            $table->index(['nasipaddress', 'acctstarttime']);
        });

        // radpostauth - Post-authentication logging
        Schema::create('radpostauth', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 64)->index();
            $table->string('pass', 64);
            $table->string('reply', 32);
            $table->string('authdate', 32);
            $table->string('nasipaddress', 15)->nullable();

            $table->index(['username', 'authdate']);
        });

        // nas - Network Access Server (Mikrotik routers)
        Schema::create('nas', function (Blueprint $table) {
            $table->id();
            $table->string('nasname', 128)->unique();
            $table->string('shortname', 32);
            $table->string('type', 30)->default('other');
            $table->integer('ports')->nullable();
            $table->string('secret', 60);
            $table->string('server', 64)->nullable();
            $table->string('community', 50)->nullable();
            $table->string('description', 200)->nullable();

            $table->index('nasname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radpostauth');
        Schema::dropIfExists('radacct');
        Schema::dropIfExists('radusergroup');
        Schema::dropIfExists('radgroupreply');
        Schema::dropIfExists('radgroupcheck');
        Schema::dropIfExists('radreply');
        Schema::dropIfExists('radcheck');
        Schema::dropIfExists('nas');
    }
};

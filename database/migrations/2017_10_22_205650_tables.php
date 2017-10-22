<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection()->getPdo()->exec("
            CREATE TABLE `currencies` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(3) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `IDX_code` (`code`)
            )
            ENGINE=InnoDB;
        ");

        DB::connection()->getPdo()->exec("
           CREATE TABLE `clients` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(1024) NULL DEFAULT NULL,
                `city` VARCHAR(512) NULL DEFAULT NULL,
                `country` VARCHAR(512) NULL DEFAULT NULL,
                `currency_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                `balance` DECIMAL(16,10) UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `IDX_name` (`name`),
                INDEX `FK_currency` (`currency_id`),
                CONSTRAINT `FK_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
            )
            ENGINE=InnoDB;
        ");

        DB::connection()->getPdo()->exec("
            CREATE TABLE `rates_on_dates` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `currency_id` INT(10) UNSIGNED NULL DEFAULT NULL,
                `date` DATE NULL DEFAULT NULL,
                `usd_rate` DECIMAL(16,10) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `IDX_currency_id_date` (`currency_id`, `date`)
            )
            ENGINE=InnoDB;s
        ");

        DB::connection()->getPdo()->exec("
            CREATE TABLE `events` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` INT(11) NULL DEFAULT NULL,
                `participant_client_id` INT(11) NULL DEFAULT NULL,
                `event_type` TINYINT(4) NULL DEFAULT NULL,
                `amount` DECIMAL(16,10) NULL DEFAULT NULL,
                `date` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            )
            ENGINE=InnoDB;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

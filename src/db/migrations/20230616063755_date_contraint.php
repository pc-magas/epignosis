<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DateContraint extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE vaccations add constraint date_range_valid check (vaccations.`from`<=vaccations.until);');
    }

    public function down(): void
    {
        dump(__CLASS__."::".__FUNCTION__);
        $this->execute('ALTER TABLE vaccations drop constraint date_range_valid');
    }
}

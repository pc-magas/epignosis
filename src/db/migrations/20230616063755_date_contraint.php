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
        $this->execute('ALTER TABLE vaccations drop constraint IF EXISTS date_range_valid');
    }
}

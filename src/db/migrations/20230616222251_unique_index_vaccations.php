<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UniqueIndexVaccations extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('DROP INDEX IF EXISTS vaccation_range_index ON vaccations;');
    }

    public function down(): void
    {
        $this->execute('DROP INDEX IF EXISTS vaccation_range_index ON vaccations;');
    }
}

<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Docman_ItemFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PFUser;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class ItemImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testImport(): void
    {
        $permission_manager = Mockery::mock(PermissionsManager::class);
        $item_factory       = Mockery::mock(Docman_ItemFactory::class);

        $node          = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><item/>');
        $node_importer = Mockery::mock(NodeImporter::class);
        $post_importer = Mockery::mock(PostDoNothingImporter::class);
        $parent_item   = Mockery::mock(Docman_Item::class)->shouldReceive(['getId' => 13])->getMock();
        $user          = Mockery::mock(PFUser::class)->shouldReceive(['getId' => 101])->getMock();
        $properties    = ImportProperties::buildLink('My document', 'The description', 'https://example.test');

        $created_item = Mockery::mock(Docman_Item::class)->shouldReceive(['getId' => 14])->getMock();

        $item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('My document', 'The description', 13, 100, 0, 101, 3, null, 'https://example.test')
            ->once()
            ->andReturn($created_item);

        $permission_manager
            ->shouldReceive('clonePermissions')
            ->with(13, 14, ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE'])
            ->once();

        $post_importer
            ->shouldReceive('postImport')
            ->with($node_importer, $node, $created_item, $user);

        $importer = new ItemImporter($permission_manager, $item_factory);
        $importer->import($node, $node_importer, $post_importer, $parent_item, $user, $properties);
    }
}

<?php

declare(strict_types=1);

namespace InoERP\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for SecureSearchBuilder class
 */
class SecureSearchBuilderTest extends TestCase
{
    public function testBuildWhereClauseSimpleLike(): void
    {
        $searchArray = ['name', 'email'];
        $input = [
            'name' => ['john'],
            'email' => ['john@example.com'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'users');
        
        $this->assertStringContainsString('WHERE', $result['where_clause']);
        $this->assertStringContainsString('LIKE', $result['where_clause']);
        $this->assertArrayHasKey(':param_0', $result['params']);
        $this->assertArrayHasKey(':param_1', $result['params']);
        $this->assertEquals(2, $result['count']);
    }

    public function testBuildWhereClauseWithInClause(): void
    {
        $searchArray = ['status'];
        $input = [
            'status' => ['active,pending,completed'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'orders');
        
        $this->assertStringContainsString('IN', $result['where_clause']);
        $this->assertCount(3, $result['params']); // 3 values in IN clause
        $this->assertEquals(1, $result['count']);
    }

    public function testBuildWhereClauseWithOperators(): void
    {
        $searchArray = ['age', 'price'];
        $input = [
            'age' => ['>18'],
            'price' => ['<100'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'products');
        
        $this->assertStringContainsString('>', $result['where_clause']);
        $this->assertStringContainsString('<', $result['where_clause']);
        $this->assertEquals(2, $result['count']);
    }

    public function testBuildWhereClauseWithEqualsOperator(): void
    {
        $searchArray = ['id'];
        $input = [
            'id' => ['=42'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'users');
        
        $this->assertStringContainsString('=', $result['where_clause']);
        $this->assertEquals(1, $result['count']);
    }

    public function testBuildWhereClauseWithNotEquals(): void
    {
        $searchArray = ['status'];
        $input = [
            'status' => ['!=deleted'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'users');
        
        $this->assertStringContainsString('!=', $result['where_clause']);
        $this->assertEquals(1, $result['count']);
    }

    public function testBuildWhereClauseWithIdColumn(): void
    {
        $searchArray = ['user_id'];
        $input = [
            'user_id' => ['123'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'users');
        
        $this->assertStringContainsString('=', $result['where_clause']);
        $this->assertEquals(1, $result['count']);
    }

    public function testBuildWhereClauseWithNull(): void
    {
        $searchArray = ['deleted_at'];
        $input = [
            'deleted_at' => ['null'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'users');
        
        $this->assertStringContainsString('IS NULL', $result['where_clause']);
        $this->assertEquals(1, $result['count']);
    }

    public function testBuildWhereClauseEmptyInput(): void
    {
        $searchArray = ['name'];
        $input = [];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'users');
        
        $this->assertEquals('', $result['where_clause']);
        $this->assertEquals(0, $result['count']);
    }

    public function testBuildWhereClauseInvalidColumn(): void
    {
        $searchArray = ['invalid-column', 'valid_column'];
        $input = [
            'invalid-column' => ['test'],
            'valid_column' => ['test'],
        ];
        $params = [];
        
        $result = \SecureSearchBuilder::buildWhereClause($searchArray, $input, $params, 'users');
        
        // Should skip invalid column name
        $this->assertEquals(1, $result['count']);
    }

    public function testBuildSearchQueryBasic(): void
    {
        $whereClause = 'WHERE `name` LIKE :param_0';
        $params = [':param_0' => '%john%'];
        
        $result = \SecureSearchBuilder::buildSearchQuery(
            'users',
            $whereClause,
            $params,
            'created_at',
            'DESC',
            10,
            0
        );
        
        $this->assertStringContainsString('SELECT', $result['sql']);
        $this->assertStringContainsString('FROM `users`', $result['sql']);
        $this->assertStringContainsString('WHERE', $result['sql']);
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $result['sql']);
        $this->assertStringContainsString('LIMIT 10', $result['sql']);
        $this->assertStringContainsString('OFFSET 0', $result['sql']);
    }

    public function testBuildSearchQueryWithGroupBy(): void
    {
        $whereClause = 'WHERE `status` = :param_0';
        $params = [':param_0' => 'active'];
        
        $result = \SecureSearchBuilder::buildSearchQuery(
            'orders',
            $whereClause,
            $params,
            '',
            '',
            0,
            0,
            'customer_id',
            'total_amount'
        );
        
        $this->assertStringContainsString('GROUP BY `customer_id`', $result['sql']);
        $this->assertStringContainsString('SUM(`total_amount`)', $result['sql']);
    }

    public function testBuildSearchQueryInvalidTableName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        \SecureSearchBuilder::buildSearchQuery(
            'invalid-table',
            '',
            [],
            '',
            '',
            0,
            0
        );
    }

    public function testBuildSearchQueryInvalidOrderBy(): void
    {
        $whereClause = '';
        $params = [];
        
        $result = \SecureSearchBuilder::buildSearchQuery(
            'users',
            $whereClause,
            $params,
            'invalid-column',
            'ASC',
            0,
            0
        );
        
        // Should not include invalid order by
        $this->assertStringNotContainsString('ORDER BY', $result['sql']);
    }

    public function testBuildSearchQueryInvalidOrderDir(): void
    {
        $whereClause = '';
        $params = [];
        
        $result = \SecureSearchBuilder::buildSearchQuery(
            'users',
            $whereClause,
            $params,
            'name',
            'INVALID',
            0,
            0
        );
        
        // Should default to ASC
        $this->assertStringContainsString('ORDER BY `name` ASC', $result['sql']);
    }
}
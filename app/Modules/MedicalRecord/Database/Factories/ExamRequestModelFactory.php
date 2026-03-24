<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloSolicitacaoExame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloSolicitacaoExame>
 */
final class ExamRequestModelFactory extends Factory
{
    protected $model = ModeloSolicitacaoExame::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Rotina', 'Cardiologia', 'Endocrinologia', 'Neurologia', 'Reumatologia', 'Nefrologia'];

        $allItems = [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566'],
            ['id' => 'glicemia', 'name' => 'Glicemia em jejum', 'tuss_code' => '40302213'],
            ['id' => 'colesterol', 'name' => 'Colesterol total e frações', 'tuss_code' => '40302140'],
            ['id' => 'tsh', 'name' => 'TSH ultrassensível', 'tuss_code' => '40302787'],
            ['id' => 'creatinina', 'name' => 'Creatinina sérica', 'tuss_code' => '40302124'],
            ['id' => 'tgo', 'name' => 'TGO (AST)', 'tuss_code' => '40302590'],
            ['id' => 'tgp', 'name' => 'TGP (ALT)', 'tuss_code' => '40302604'],
            ['id' => 'ureia', 'name' => 'Ureia sérica', 'tuss_code' => '40302809'],
            ['id' => 'sodio', 'name' => 'Sódio sérico', 'tuss_code' => '40302779'],
            ['id' => 'potassio', 'name' => 'Potássio sérico', 'tuss_code' => '40302736'],
            ['id' => 'acido_urico', 'name' => 'Ácido úrico', 'tuss_code' => '40302019'],
        ];

        $count = fake()->numberBetween(5, 10);
        $items = fake()->randomElements($allItems, min($count, count($allItems)));

        return [
            'user_id' => User::factory()->doctor(),
            'nome' => fake()->sentence(3),
            'categoria' => fake()->optional(0.7)->randomElement($categories),
            'itens' => $items,
        ];
    }
}

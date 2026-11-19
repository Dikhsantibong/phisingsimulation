<?php

namespace Tests\Feature;

use App\Models\Respondent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RespondentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_respondent_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();

        $this->actingAs($user)
            ->delete(route('respondents.destroy', $respondent))
            ->assertRedirect();

        $this->assertDatabaseMissing('respondents', ['id' => $respondent->id]);
    }

    public function test_several_respondents_can_be_deleted_at_once(): void
    {
        $user = User::factory()->create();
        $toDelete = Respondent::factory()->count(3)->create();
        $toKeep = Respondent::factory()->create();

        $this->actingAs($user)
            ->delete(route('respondents.bulk-destroy'), [
                'ids' => $toDelete->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($toDelete as $respondent) {
            $this->assertDatabaseMissing('respondents', ['id' => $respondent->id]);
        }

        $this->assertDatabaseHas('respondents', ['id' => $toKeep->id]);
    }

    public function test_bulk_destroy_requires_at_least_one_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('respondents.bulk-destroy'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_deleting_respondents_requires_authentication(): void
    {
        $respondent = Respondent::factory()->create();

        $this->delete(route('respondents.destroy', $respondent))
            ->assertRedirect(route('login'));
    }
}

<?php

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\User\Models\User;

beforeEach(function () {

    $this->auth_user = User::factory()->create();

    $this->assigned_department = Department::factory()->create();

    $this->project = Project::factory()->create();

    $this->checklist = Checklist::factory()->create();
    $this->checklist_template = ChecklistTemplate::factory()->create();

    $this->task_template = TaskTemplate::factory()->create();

    $this->sector = Sector::factory()->create();

    $this->genre = Genre::factory()->create();

    $this->department = Department::factory()->create();

});

/** @todo there are no checks in controller for authorization */

//test('users who cant access checklist templates cant create tasks on them', function () {
//
//    $this->actingAs($this->auth_user);
//
//    $this->post('/task_templates', [
//        'name' => 'TestTask',
//        'description' => "This is a description",
//        'checklist_id' => $this->checklist->id,
//        'checklist_template_id' => $this->checklist_template->id
//    ])->assertStatus(403);
//});

test('users that can update checklist templates can create tasks for it', function () {

    $this->actingAs($this->auth_user);

    $this->post('/task_templates', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $this->checklist->id,
        'checklist_template_id' => $this->checklist_template->id
    ]);

    $this->assertDatabaseHas('task_templates', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_template_id' => $this->checklist_template->id
    ]);

});

test('users who can update checklist templates can update its tasks', function () {

    $this->actingAs($this->auth_user);

    $this->patch("/task_templates/{$this->task_template->id}", [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_id' => $this->checklist->id,
        'checklist_template_id' => $this->checklist_template->id
    ]);

    $this->assertDatabaseHas('task_templates', [
        'name' => 'TestTask',
        'description' => "This is a description",
        'checklist_template_id' => $this->checklist_template->id
    ]);


});

test('users who can update checklist templates can delete its tasks', function () {

    $this->actingAs($this->auth_user);

    $this->delete("/task_templates/{$this->task_template->id}");

    $this->assertDatabaseMissing('task_templates', [
        'id' => $this->task_template->id
    ]);
});



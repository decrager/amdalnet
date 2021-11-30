<?php

use App\Http\Controllers\BaganAlirController;
use App\Http\Controllers\ExportDocument;
use App\Http\Controllers\UklUplCommentController;
use App\Http\Controllers\ProjectMapAttachmentController;
use App\Http\Resources\UserResource;
use App\Laravue\Acl;
use App\Laravue\Faker;
use App\Laravue\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChangeTypeController;
use App\Http\Controllers\PieParamController;
use App\Http\Controllers\ImpactIdentificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::apiResource('home', 'HomeController');
Route::apiResource('webgis', 'WebgisController');

Route::namespace('Api')->group(function () {
    Route::post('auth/login', 'AuthController@login');
    Route::group(['middleware' => 'auth:sanctum'], function () {
        // Auth routes
        Route::get('auth/user', 'AuthController@user');
        Route::post('auth/logout', 'AuthController@logout');

        Route::get('/user', function (Request $request) {
            return new UserResource($request->user());
        });

        // Api resource routes
        Route::apiResource('roles', 'RoleController')->middleware('permission:' . Acl::PERMISSION_MANAGE_PERMISSION);
        Route::apiResource('users', 'UserController')->middleware('permission:' . Acl::PERMISSION_MANAGE_USER);
        Route::apiResource('permissions', 'PermissionController')->middleware('permission:' . Acl::PERMISSION_MANAGE_PERMISSION);

        Route::get('doc-uklupl', [ExportDocument::class, 'ExportUklUpl']);

        // Custom routes
        Route::put('users/{user}', 'UserController@update');
        Route::put('uploadAvatar/{user}', 'UserController@updateAvatar');
        Route::get('users/{user}/permissions', 'UserController@permissions')->middleware('permission:' . Acl::PERMISSION_MANAGE_PERMISSION);
        Route::put('users/{user}/permissions', 'UserController@updatePermissions')->middleware('permission:' . Acl::PERMISSION_MANAGE_PERMISSION);
        Route::get('roles/{role}/permissions', 'RoleController@permissions')->middleware('permission:' . Acl::PERMISSION_MANAGE_PERMISSION);
    });
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('workspace/session/init', 'WorkspaceController@sessionInit');
    Route::get('workspace/config/{id}', 'WorkspaceController@getConfig');
});

Route::post('workspace/template/import', 'WorkspaceController@importTemplate');
Route::post('workspace/document/track', 'WorkspaceController@track');
Route::post('workspace/document/upload', 'WorkspaceController@upload');
Route::post('workspace/document/download', 'WorkspaceController@download');
Route::post('workspace/document/convert', 'WorkspaceController@convert');
Route::post('workspace/document/delete', 'WorkspaceController@delete');
Route::post('workspace/document/assets', 'WorkspaceController@assets');
Route::post('workspace/document/files', 'WorkspaceController@files');

// Fake APIs
Route::get('/table/list', function () {
    $rowsNumber = mt_rand(20, 30);
    $data = [];
    for ($rowIndex = 0; $rowIndex < $rowsNumber; $rowIndex++) {
        $row = [
            'author' => Faker::randomString(mt_rand(5, 10)),
            'display_time' => Faker::randomDateTime()->format('Y-m-d H:i:s'),
            'id' => mt_rand(100000, 100000000),
            'pageviews' => mt_rand(100, 10000),
            'status' => Faker::randomInArray(['deleted', 'published', 'draft']),
            'title' => Faker::randomString(mt_rand(20, 50)),
        ];

        $data[] = $row;
    }

    return response()->json(new JsonResponse(['items' => $data]));
});

Route::get('/orders', function () {
    $rowsNumber = 8;
    $data = [];
    for ($rowIndex = 0; $rowIndex < $rowsNumber; $rowIndex++) {
        $row = [
            'order_no' => 'LARAVUE' . mt_rand(1000000, 9999999),
            'price' => mt_rand(10000, 999999),
            'status' => Faker::randomInArray(['success', 'pending']),
        ];

        $data[] = $row;
    }

    return response()->json(new JsonResponse(['items' => $data]));
});

Route::get('/articles', function () {
    $rowsNumber = 10;
    $data = [];
    for ($rowIndex = 0; $rowIndex < $rowsNumber; $rowIndex++) {
        $row = [
            'id' => mt_rand(100, 10000),
            'display_time' => Faker::randomDateTime()->format('Y-m-d H:i:s'),
            'title' => Faker::randomString(mt_rand(20, 50)),
            'author' => Faker::randomString(mt_rand(5, 10)),
            'comment_disabled' => Faker::randomBoolean(),
            'content' => Faker::randomString(mt_rand(100, 300)),
            'content_short' => Faker::randomString(mt_rand(30, 50)),
            'status' => Faker::randomInArray(['deleted', 'published', 'draft']),
            'forecast' => mt_rand(100, 9999) / 100,
            'image_uri' => 'https://via.placeholder.com/400x300',
            'importance' => mt_rand(1, 3),
            'pageviews' => mt_rand(10000, 999999),
            'reviewer' => Faker::randomString(mt_rand(5, 10)),
            'timestamp' => Faker::randomDateTime()->getTimestamp(),
            'type' => Faker::randomInArray(['US', 'VI', 'JA']),

        ];

        $data[] = $row;
    }

    return response()->json(new JsonResponse(['items' => $data, 'total' => mt_rand(1000, 10000)]));
});

Route::get('articles/{id}', function ($id) {
    $article = [
        'id' => $id,
        'display_time' => Faker::randomDateTime()->format('Y-m-d H:i:s'),
        'title' => Faker::randomString(mt_rand(20, 50)),
        'author' => Faker::randomString(mt_rand(5, 10)),
        'comment_disabled' => Faker::randomBoolean(),
        'content' => Faker::randomString(mt_rand(100, 300)),
        'content_short' => Faker::randomString(mt_rand(30, 50)),
        'status' => Faker::randomInArray(['deleted', 'published', 'draft']),
        'forecast' => mt_rand(100, 9999) / 100,
        'image_uri' => 'https://via.placeholder.com/400x300',
        'importance' => mt_rand(1, 3),
        'pageviews' => mt_rand(10000, 999999),
        'reviewer' => Faker::randomString(mt_rand(5, 10)),
        'timestamp' => Faker::randomDateTime()->getTimestamp(),
        'type' => Faker::randomInArray(['US', 'VI', 'JA']),

    ];

    return response()->json(new JsonResponse($article));
});

Route::get('articles/{id}/pageviews', function ($id) {
    $pageviews = [
        'PC' => mt_rand(10000, 999999),
        'Mobile' => mt_rand(10000, 999999),
        'iOS' => mt_rand(10000, 999999),
        'android' => mt_rand(10000, 999999),
    ];
    $data = [];
    foreach ($pageviews as $device => $pageview) {
        $data[] = [
            'key' => $device,
            'pv' => $pageview,
        ];
    }

    return response()->json(new JsonResponse(['pvData' => $data]));
});

Route::apiResource('project-fields', 'ProjectFieldController');
Route::apiResource('provinces', 'ProvinceController');
Route::apiResource('districts', 'DistrictController');
Route::apiResource('kblis', 'BusinessController');
Route::apiResource('kbli-env-params', 'BusinessEnvParamController');
Route::apiResource('projects', 'ProjectController');
Route::apiResource('formulator-teams', 'FormulatorTeamController');
Route::apiResource('environmental-experts', 'EnvironmentalExpertController');
Route::apiResource('oss-projects', 'OssProjectController');
Route::apiResource('responder-types', 'ResponderTypeController');
Route::apiResource('feedbacks', 'FeedbackController');
Route::apiResource('support-docs', 'SupportDocController');
Route::apiResource('announcements', 'AnnouncementController');
Route::apiResource('initiators', 'InitiatorController');
Route::apiResource('lpjp', 'LpjpController');
Route::apiResource('formulators', 'FormulatorController');
Route::apiResource('expert-banks', 'ExpertBankController');
Route::apiResource('public-consultations', 'PublicConsultationController');
Route::apiResource('rona-awals', 'RonaAwalController');
Route::apiResource('components', 'ComponentController');
Route::apiResource('project-stages', 'ProjectStageController');
Route::apiResource('sops', 'SopController');
Route::apiResource('component-types', 'ComponentTypeController');
Route::apiResource('app-params', 'AppParamController');
Route::get('initiatorsByEmail', 'InitiatorController@showByEmail');
Route::get('formulatorsByEmail', 'FormulatorController@showByEmail');
Route::get('lpjpsByEmail', 'LpjpController@showByEmail');
Route::get('expertByEmail', 'ExpertBankController@showByEmail');
Route::apiResource('impact-identifications', 'ImpactIdentificationController');
Route::apiResource('env-params', 'EnvParamController');
Route::apiResource('params', 'ParamController');
Route::apiResource('units', 'UnitController');
Route::apiResource('project-components', 'ProjectComponentController');
Route::apiResource('project-rona-awals', 'ProjectRonaAwalController');
Route::apiResource('change-types', 'ChangeTypeController');
Route::apiResource('institutions', 'InstitutionController');
Route::apiResource('andal-composing', 'AndalComposingController');
Route::apiResource('matriks-rkl', 'MatriksRKLController');
Route::apiResource('matriks-rpl', 'MatriksRPLController');
Route::apiResource('testing-verification', 'TestingVerificationController');
Route::apiResource('testing-meeting', 'TestingMeetingController');
Route::apiResource('meeting-report', 'MeetingReportController');
Route::apiResource('test-verif-rkl-rpl', 'TestVerifRKLRPLController');
Route::apiResource('test-meet-rkl-rpl', 'TestMeetRKLRPLController');
Route::apiResource('meet-report-rkl-rpl', 'MeetReportRKLRPLController');
Route::apiResource('feasibility-test', 'FeasibilityTestController');
Route::apiResource('skkl', 'SKKLController');
Route::apiResource('impact-studies', 'ImpactStudyController');
Route::get('ukl-upl-comment/{id}', [UklUplCommentController::class, 'index']);
Route::post('ukl-upl-comment', [UklUplCommentController::class, 'store']);
Route::get('ka-docx/{id}', [ExportDocument::class, 'KADocx']);
Route::apiResource('scoping', 'ScopingController');
Route::apiResource('sub-project-components', 'SubProjectComponentController');
Route::apiResource('sub-project-rona-awals', 'SubProjectRonaAwalController');
Route::get('bagan-alir/{id}', [BaganAlirController::class, 'baganAlirUklUpl']);
Route::get('project-map', [ProjectMapAttachmentController::class, 'index']);
Route::get('change-types', [ChangeTypeController::class, 'index']);
Route::get('pie-params', [PieParamController::class, 'index']);
Route::post('upload-map', [ProjectMapAttachmentController::class, 'post']);
Route::get('download-map/{id}', [ProjectMapAttachmentController::class, 'download']);
Route::apiResource('manage-approach', 'ManageApproachController');
Route::post('upload-ka-doc', [ExportDocument::class, 'saveKADoc']);
Route::get('pie-entries', [ImpactIdentificationController::class, 'pieEntries']);
Route::post('change-types', [ChangeTypeController::class, 'addChangeType']);
Route::get('get-document-ka/{id}', [ExportDocument::class, 'getDocKA']);
Route::get('form-ka-pdf/{id}', [ExportDocument::class, 'ExportKAPdf']);
Route::apiResource('andal-clone', 'AndalCloneController');
Route::get('map/{id}', [ProjectMapAttachmentController::class, 'show']);

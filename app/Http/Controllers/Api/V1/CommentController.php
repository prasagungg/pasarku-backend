<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Comment\{CommentCommands, CommentQueries};
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    protected $commentCommands, $commentQueries;

    public function __construct()
    {
        $this->middleware('auth:api');

        $this->commentCommands = new CommentCommands();
        $this->commentQueries = new CommentQueries();
    }

    public function getCommentByPost($post_id, Request $request)
    {
        try {
            $limit = is_numeric($request->limit) ? filter_var($request->limit, FILTER_VALIDATE_INT) : 10;
            $orderby = !empty($request->orderby) ? $request->orderby : null;
            $sort = !empty($request->sort) ? $request->sort : 'asc';
            $filter = $request->filter ?? [];

            $post_comments = $this->commentQueries->getCommentByPost($post_id, $limit, $orderby, $sort, $filter);

            return response()->successWithData($post_comments);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function comment($post_id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'comment' => 'required',
                'parent_id' => 'nullable|exists:post_comment,id'
            ]);

            if ($validator->fails()) {
                $errors = collect();

                foreach ($validator->errors()->getMessages() as $key => $value) {
                    foreach ($value as $error) {
                        $errors[$key] = $error;
                    }
                }

                return response()->errorValidation($errors);
            }

            $comment = $this->commentCommands->comment($post_id, $request);

            return response()->successWithData($comment, 201);
        } catch (Exception $e) {
            return $this->respondErrorException($e, $request);
        }
    }
}

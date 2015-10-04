<?php

namespace SilexTodos;

use RestMachine\Resource;
use RestMachine\Context;

class TodoResource {

    static function validator() {
        return function($context) {
            return !isset($context->entity) || Todo::isValid($context->entity);
        };
    }

    static function defaults() {
        return Resource::create()
            ->availableMediaTypes(['application/json'])
            ->isMalformed(function (Context $context) {
                if ($context->getRequest()->getContent()) {
                    $context->entity = json_decode($context->getRequest()->getContent());
                    return json_last_error();
                }
                return false;
            });
    }

    static function collection(\PDO $db) {
        return Resource::create(self::defaults())
            ->allowedMethods(['GET', 'POST'])
            ->isProcessable(self::validator())
            ->post(function ($context) use ($db) {
                $context->entity->id = Todo::create($db, $context->entity);
            })
            ->handleCreated(function($context) {
                return $context->entity;
            })
            ->handleOk(function (Context $context) use ($db) {
                return Todo::fetchAll($db);
            });
    }

    static function entity(\PDO $db, $id) {
        return Resource::create(self::defaults())
            ->allowedMethods(['GET', 'PUT', 'DELETE'])
            ->isProcessable(self::validator())
            ->isCanPutToMissing(false)
            ->isNew(false)
            ->isRespondWithEntity(function(Context $context) {
                return $context->getRequest()->isMethod('PUT');
            })
            ->isExists(function($context) use ($db, $id) {
                return Todo::exists($db, $id);
            })
            ->put(function($context) use ($db, $id) {
                Todo::update($db, $id, $context->entity);
            })
            ->delete(function($context) use ($db, $id) {
                Todo::delete($db, $id);
            })
            ->handleOk(function(Context $context) use ($db, $id) {
                return Todo::fetchOne($db, $id);
            });
    }

}

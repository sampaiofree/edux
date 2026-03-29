<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class TenantAwareEloquentUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier without tenant global scopes.
     *
     * Credential-based login remains tenant-scoped via retrieveByCredentials.
     *
     * @param  mixed  $identifier
     * @return (\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model)|null
     */
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->withoutGlobalScopes()
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token
     * without tenant global scopes.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return (\Illuminate\Contracts\Auth\Authenticatable&\Illuminate\Database\Eloquent\Model)|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $model = $this->createModel();

        $retrievedModel = $this->newModelQuery($model)
            ->withoutGlobalScopes()
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();

        if (! $retrievedModel) {
            return null;
        }

        $rememberToken = $retrievedModel->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $retrievedModel : null;
    }
}

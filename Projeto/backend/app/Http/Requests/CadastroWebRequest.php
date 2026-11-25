<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CadastroWebRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo_cliente' => strtoupper($this->string('tipo_cliente', Cliente::TIPO_PF)->toString()),
            'cpf' => preg_replace('/\D/', '', $this->string('cpf')->toString()),
            'cnpj' => preg_replace('/\D/', '', $this->string('cnpj')->toString()),
        ]);
    }

    public function rules(): array
    {
        $cliente = $this->routeIs('web.register.store') || $this->input('perfil') === 'CLIENTE';

        $rules = [
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:72|confirmed',
        ];

        if (! $this->routeIs('web.register.store')) {
            $rules['perfil'] = ['required', Rule::in(['ADMIN', 'ATENDENTE', 'CLIENTE'])];
        }

        if (! $cliente) {
            return $rules + ['nome' => 'required|string|max:120'];
        }

        $rules['tipo_cliente'] = ['required', Rule::in([Cliente::TIPO_PF, Cliente::TIPO_PJ])];

        if ($this->input('tipo_cliente') === Cliente::TIPO_PJ) {
            return $rules + [
                'razao_social' => 'required|string|max:150',
                'cnpj' => ['required', 'size:14', 'unique:clientes,cnpj', new Cnpj],
                'representante_legal' => 'required|string|max:150',
            ];
        }

        return $rules + [
            'nome' => 'required|string|max:120',
            'cpf' => ['required', 'size:11', 'unique:clientes,cpf', new Cpf],
            'data_nascimento' => 'required|date|before:today',
            'tipo_conta' => 'nullable|in:CORRENTE,POUPANCA,SALARIO',
        ];
    }
}

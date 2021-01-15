<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\User;

use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function login(Request $request){
        $data = $request->all();
        $validacao = Validator::make($data, [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
        ]);
        if($validacao->fails()){
            return ['status'=>false, 'validacao'=>true, 'erros'=>$validacao->errors()];
        }
        if(Auth::attempt(['email' => $data['email'],'password'=> $data['password']])){
            $user= auth()->user();
            $user->token = $user->createToken($user->email)->accessToken;
            //$user->imagem = asset($user->imagem);
            return ['status'=>true, 'usuario'=>$user];
        }
        else{
            return ['status'=>false];
        }
    }

    public function cadastro(Request $request){
        $data = $request->all();
        $validacao = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if($validacao->fails()){
            return ['status'=>false, 'validacao'=>true, 'erros'=>$validacao->errors()];

        }
        $imagem = "/perfils/padrao.png";

        $user = User::create([
            'name' => $data['name'],
            'email'=> $data['email'],
            'password' => bcrypt($data['password']),
            'imagem' => $imagem
        ]);
        $user->token = $user->createToken($user->email)->accessToken;
        //$user->imagem = asset($user->imagem);
        return ['status'=>true, 'usuario'=>$user];
    }

    public function perfil(Request $request){
        $user =  $request->user();
        $data = $request->all();
        if(isset($data['password'])){
            $validacao = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => ['required','string','email','max:255',Rule::unique('users')->ignore($user->id) ],
                'password' => 'required|string|min:6|confirmed',
            ]);
            if($validacao->fails()){
                return ['status'=>false, 'validacao'=>true, 'erros'=>$validacao->errors()];
            }
           $user->password = bcrypt($data['password']);
        }else{
            $validacao = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => ['required','string','email','max:255',Rule::unique('users')->ignore($user->id) ]
            ]);
    
            if($validacao->fails()){
                return ['status'=>false, 'validacao'=>true, 'erros'=>$validacao->errors()];
            }
            $user->name =  $data['name'];
            $user->email =  $data['email'];
        }
        if(isset($data['imagem'])){
    
            //validaçao Base64
            Validator::extend('base64image', function ($attribute, $value, $parameters, $validator) {
                $explode = explode(',', $value);
                $allow = ['png', 'jpg', 'svg','jpeg'];
                $format = str_replace(
                    [
                        'data:image/',
                        ';',
                        'base64',
                    ],
                    [
                        '', '', '',
                    ],
                    $explode[0]
                );
                // check file format
                if (!in_array($format, $allow)) {
                    return false;
                }
                // check base64 format
                if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
                    return false;
                }
                return true;
            });
    
            $valiacao = Validator::make($data, [
                'imagem' => 'base64image',
    
            ],['base64image'=>'Imagem inválida']);
    
            if($valiacao->fails()){
                return ['status'=>false, 'validacao'=>true, 'erros'=>$validacao->errors()];
            }
    
            //estrutura da Imagem de Perfil
            $time = time();
            $dirPai = 'perfils';
            $dirImagem = $dirPai.DIRECTORY_SEPARATOR.'perdil_id'.$user->id;
            $ext = substr($data['imagem'], 11, strpos($data['imagem'], ';') -11);
            $urlImage =  $dirImagem.DIRECTORY_SEPARATOR.$time.'.'.$ext;
    
            $file = str_replace('data:image/'.$ext.';base64,', '', $data['imagem'] );
            $file = base64_decode($file);
    
            if(!file_exists($dirPai)){
                mkdir($dirPai,0700);
            }
            if(!file_exists($dirImagem)){
                mkdir($dirImagem,0700);
            }
    
            if($user->imagem){
                $imgUser = str_replace(asset('/'),'',$user->imagem);
                if(file_exists($imgUser)){
                   unlink($imgUser);
                } 
            }
    
            file_put_contents($urlImage,$file);
            $user->imagem = $urlImage;
          
        }

        $user->save();
        //$user->imagem = asset($user->imagem);
        $user->token = $user->createToken($user->email)->accessToken;
        return ['status'=>true, 'usuario'=>$user];
    }

    public function amigo(Request $request)
    {
        $user =  $request->user();
        $amigo = User::find($request->id);

        if($amigo && ($user->id != $amigo->id)){
            $user->amigos()->toggle($amigo->id);
            return ['status'=>true, 'amigos'=>$user->amigos,"seguidores"=>$amigo->seguidores];
        }else{
            return ['status'=>false,'erro'=>"Usuario nao existe"];
        }
   
    }
    
    public function listaamigos(Request $request)
    {
      $user = $request->user();
      if($user){
        return ['status'=>true,"amigos"=>$user->amigos,"seguidores"=>$user->seguidores];
      }else{
        return ['status'=>false,'erro'=>'Esse usuário não existe!'];
      }
    }

    public function listaamigospagina($id, Request $request)
    {
      $user = User::find($id);
      $userLogado = $request->user();
      if($user){
        return ['status'=>true,"amigos"=>$user->amigos,"amigoslogado"=>$userLogado->amigos,"seguidores"=>$user->seguidores];
      }else{
        return ['status'=>false,'erro'=>'Esse usuário não existe!'];
      }
    }
}

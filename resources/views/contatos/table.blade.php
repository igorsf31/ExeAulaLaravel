<table class="table table-responsive" id="contatos-table">
    <thead>
        <th>Nome</th>
        <th>Email</th>
        <th>Mensagem</th>
        <th colspan="3">Ação</th>
    </thead>
    <tbody>
    @foreach($contatos as $contato)
        <tr>
            <td>{!! $contato->nome !!}</td>
            <td>{!! $contato->email !!}</td>
            <td>{!! $contato->mensagem !!}</td>
            <td>
                {!! Form::open(['route' => ['contatos.destroy', $contato->id], 'method' => 'delete']) !!}
                <div class='btn-group'>
                    <a href="{!! route('contatos.show', [$contato->id]) !!}" class='btn btn-default btn-xs' title='Visualizar Dados'><i class="glyphicon glyphicon-eye-open"></i></a>
                </div>
                {!! Form::close() !!}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
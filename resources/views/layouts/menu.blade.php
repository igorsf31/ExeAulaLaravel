<li class="{{ Request::is('newspapers*') ? 'active' : '' }}">
    <a href="{!! route('newspapers.index') !!}"><i class="fa fa-edit"></i><span>Blog</span></a>
</li>
<li class="{{ Request::is('categories*') ? 'active' : '' }}">
    <a href="{!! route('newspapers.categories.index') !!}"><i class="fa fa-edit"></i><span>Categoria</span></a>
</li>
<li class="{{ Request::is('contatos*') ? 'active' : '' }}">
    <a href="{!! route('contatos.index') !!}"><i class="fa fa-edit"></i><span>Contatos</span></a>
</li>


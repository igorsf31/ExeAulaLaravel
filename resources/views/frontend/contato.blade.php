



@include('frontend.topo')

<div class="container">
<h3>Enviar Mensagem</h3>

	<form action="contato-enviar" method="post" class="form-horizontal">

		{!! csrf_field() !!}
		<div class="form-group">	
			<input type="text"  class="name form-control" name="name" placeholder="Nome" required>
		</div>
		<div class="form-group">	
			<input type="text"  class="email form-control" name="email" placeholder="E-mail" required>
		</div>
		<div class="form-group">	
			<input type="text"  class="assunto form-control" name="assunto" placeholder="Assunto" required>
		</div>
		<div class="form-group">	
			<textarea name="mensagem" class=" form-control" placeholder="Digite a mensagem..." required></textarea>
		</div>
		<div class="form-group">	
			<input type="submit" class="btn btn-primary"value="Enviar Mensagem">
		</div>
	
	</form>
</div>


@include('frontend.rodape')
<?php
$fh_language = new FH_Language ();
$languages = $fh_language->get ('languages');
?>
<div class="row">
	<div class="col-lg-4 col-md-6 col-sm-12">
		<a href="" class="fh-logo"><span><?php FH_Theme::_e (/*T[*/'FoxHunt'/*]*/); ?></span></a>
		<div class="fh-rounded fh-translucent fh-padded fh-center fh-trainer-login">
			<h5><?php FH_Theme::_e (/*T[*/'FoxHunt Login'/*]*/); ?></h5>
			<form action="" method="post">
				<label><?php FH_Theme::_e (/*T[*/'Username:'/*]*/); ?></label>
				<input class="form-control" name="username" type="text" value="" placeholder="" />
				<label><?php FH_Theme::_e (/*T[*/'Password:'/*]*/); ?></label>
				<input class="form-control" name="password" type="password" />
<?php if (sizeof ($languages) > 1) : ?>
				<label><?php FH_Theme::_e (/*T[*/'Language'/*]*/); ?>:</label>
				<?php FH_Theme::inp ('locale', '', 'select', $languages); ?>
				<br />
<?php endif; ?>
				<br />
				<button class="btn btn-block btn-success"><?php FH_Theme::_e (/*T[*/'Login &raquo;'/*]*/); ?></button>
			</form>
			<ul>
				<li><a href="?page=recover"><?php FH_Theme::_e (/*T[*/'Forgot your password?'/*]*/); ?></a></li>
				<!--li><a href="?page=register"><?php FH_Theme::_e (/*T[*/'Register a new account?'/*]*/); ?></a></li-->
			</ul>
		</div>
	</div>
</div>

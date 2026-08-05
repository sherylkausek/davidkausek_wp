jQuery(document).ready(function($){
	
	$('.formlayer-form').on('submit', function(e){
		e.preventDefault();
		
		let $form = $(this);
		$submit_Btn = $form.find('.formlayer-submit-btn'),
		$status = $form.find('.formlayer-form-status'),
		formId = $form.data('form-id');
		
		$submit_Btn.prop('disabled', true).addClass('loading');
		$status.html('').removeClass('formlayer-success-message formlayer-error-message');
		
		var formData = new FormData(this);
		
		// Client-side Validation
		var errors = [];
		
		// Email Confirmation Match
		$form.find('.formlayer-email-confirm').each(function(){
			var matchName = $(this).data('match');
			var originalVal = $form.find('input[name="' + matchName + '"]').val();
			if($(this).val() !== originalVal){
				errors.push('Emails do not match.');
			}
		});

		// Digit Limit Validation
		$form.find('input[type="number"][maxlength]').each(function(){
			var limit = parseInt($(this).attr('maxlength'));
			if($(this).val().length > limit){
				errors.push('Value exceeds maximum digit limit of ' + limit);
			}
		});

		// URL Validation
		$form.find('input[data-validate-url="1"]').each(function(){
			var val = $(this).val();
			if(val && !val.match(/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/)){
				errors.push('Please enter a valid URL.');
			}
		});

		// URL HTTPS Only Validation
		$form.find('input[data-https-only="1"]').each(function(){
			var val = $(this).val();
			if(val && !val.startsWith('https://')){
				errors.push('Only HTTPS URLs are allowed.');
			}
		});

		if(errors.length > 0){
			$status.addClass('formlayer-error-message').html(errors.join('<br>'));
			$submit_Btn.prop('disabled', false).removeClass('loading');
			return;
		}

		formData.append('action', 'formlayer_submit_form');
		formData.append('nonce', formlayer_data.nonce);
		formData.append('form_id', formId);
		
		$.ajax({
			url: formlayer_data.ajax_url,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response){
				$submit_Btn.prop('disabled', false).removeClass('loading');
				
				if(response.success){
					var settings = response.data.settings || {};
					var confirmation = settings.confirmations || { type: 'message', message: 'Thank you! Your form has been submitted successfully.', hide_form: true };
					
					if(confirmation.type === 'redirect' && confirmation.redirect_url){
						$status.addClass('formlayer-success-message').html('Redirecting...');
						window.location.href = confirmation.redirect_url;
					}else{
						if(confirmation.hide_form !== false){
							$form.find('.formlayer-form-fields-wrapper').hide();
						}
						$form[0].reset();
						$status.addClass('formlayer-success-message').html(confirmation.message || 'Thank you! Your form has been submitted successfully.');
					}
				}else{
					$status.addClass('formlayer-error-message').html(response.data.message || 'An error occurred.');
				}
			},
			error: function(jqXHR, textStatus, errorThrown){
				$submit_Btn.prop('disabled', false).removeClass('loading');
				console.error('Submission error:', textStatus, errorThrown, jqXHR.responseText);
				
				// User friendly message instead of technical parsererror
				var msg = 'An unexpected error occurred. Please try again.';
				if(textStatus === 'timeout') msg = 'Request timed out. Please check your connection.';
				
				$status.addClass('formlayer-error-message').html(msg);
			}
		});
	});
	// Rating Field Interaction
	$('.formlayer-rating .dashicons').on('click', function(){
		var value = $(this).data('value');
		var $container = $(this).closest('.formlayer-rating');
		$container.find('input').val(value);
		$container.find('.dashicons').removeClass('active');
		$(this).addClass('active').prevAll().addClass('active');
	});

	$('.formlayer-rating .dashicons').on('mouseenter', function(){
		$(this).addClass('hover').prevAll().addClass('hover');
		$(this).nextAll().removeClass('hover');
	}).on('mouseleave', '.formlayer-rating .dashicons', function(){
		$('.formlayer-rating .dashicons').removeClass('hover');
	});

  // File & Image Upload Preview
  $('.formlayer-file-real').on('change', function(e){
		let input = this,
		$wrap = $(input).closest('.formlayer-field-wrap'),
		$chosenName = $wrap.find('.formlayer-file-chosen-name');

		// Remove any existing preview container
		$wrap.find('.formlayer-file-preview-container').remove();

		if (input.files && input.files[0]) {
			let file = input.files[0];
			$chosenName.text(file.name);

			// Create preview container
			let $previewContainer = $(`
				<div class="formlayer-file-preview-container" style="margin-top:10px; display:flex; align-items:center; gap:12px; padding:10px; border:1px dashed #ccc; border-radius:6px; background:#f9f9f9; max-width: 100%;">
					<img class="formlayer-file-preview-img" src="" style="width:60px; height:60px; border-radius:4px; display:none; object-fit:cover; border:1px solid #ddd; background:#fff;">
					<span class="formlayer-file-preview-icon dashicons dashicons-document" style="font-size:36px; width:36px; height:36px; display:none; color:#5525d6; line-height:36px;"></span>
					<div style="display:flex; flex-direction:column; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:left; margin-right: auto;">
						<span class="formlayer-file-preview-name" style="font-size:13px; font-weight:600; color:#333; overflow:hidden; text-overflow:ellipsis; max-width: 200px;"></span>
						<span class="formlayer-file-preview-size" style="font-size:11px; color:#777; margin-top: 2px;"></span>
					</div>
					<span class="formlayer-file-preview-remove dashicons dashicons-no-alt" style="cursor:pointer; color:#999; font-size:20px; width:20px; height:20px; line-height:20px; text-align:center; transition:color 0.2s;" onmouseover="this.style.color='#d9534f'" onmouseout="this.style.color='#999'"></span>
				</div>
			`);

			$previewContainer.find('.formlayer-file-preview-name').text(file.name);
			
			// Format size
			let sizeStr = (file.size / 1024).toFixed(1) + ' KB';
			if (file.size > 1024 * 1024) {
				sizeStr = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
			}
			$previewContainer.find('.formlayer-file-preview-size').text(sizeStr);

			// Show preview image if it's an image
			if (file.type.match('image.*')) {
				let reader = new FileReader();
				reader.onload = function(e) {
					$previewContainer.find('.formlayer-file-preview-img').attr('src', e.target.result).show();
				}
				reader.readAsDataURL(file);
			} else {
				$previewContainer.find('.formlayer-file-preview-icon').show();
			}

			// Add click event for remove cross button
			$previewContainer.find('.formlayer-file-preview-remove').on('click', function(){
				$(input).val('');
				$chosenName.text('No file chosen');
				$previewContainer.remove();
			});

			$wrap.append($previewContainer);
		} else {
			$chosenName.text('No file chosen');
		}
	});
});

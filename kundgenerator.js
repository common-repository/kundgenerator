var Kundgenerator = {
	SiteList:null, Test: null,

	Init:function(){
		var hashKey = unescape(Kundgenerator.GetParam('hashKey', window.location.href));
		var userId = unescape(Kundgenerator.GetParam('userId', window.location.href));
		if(!Kundgenerator.IsEmptyOrWhiteSpace(hashKey))
		{
			Kundgenerator.GetWebcode(hashKey, userId);
		}
		
		jQuery('#btnChangeWebcode').unbind('click');
		jQuery('#btnChangeWebcode').click(function() { Kundgenerator.ChangeWebcode(); });
	},
	
	ChangeWebcode:function()
	{
		window.location.href = window.kgCompleteUrl;
	},
	
	GetWebcode:function(hashKey, userId)
	{
		jQuery.ajax({ 
			url: 'https://stockenapi.kundgenerator.se/WordPressWebCode.svc/GetWebCode', 
			type: 'GET', 
			dataType: 'json', 
			data: { hashKey: hashKey, userId: userId }, 
			success: function (data) 
			{ 
				var error = data.d.Error;
				var webCodeList = data.d.WebCode;
				
				var trTxtWebcode = jQuery('#trTxtWebcode');
				var trTxtOwnScriptCode = jQuery('#trTxtOwnScriptCode');
				var txtWebcode = jQuery('#txtWebcode');
				var trConnectError = jQuery('#trConnectError');
				var spanConnectError = jQuery('#spanConnectError');
				var trDllSite = jQuery('#trDllSite');
				var ddlSite = jQuery('#ddlSite');
				var txtWebcodeInfo = jQuery('#txtWebcodeInfo');
				var hdnSiteId = jQuery('#hdnSiteId');
				
				ddlSite.unbind('change');
				
				function showError(error)
				{
					trConnectError.show();
					spanConnectError.text(error);
					trTxtWebcode.hide();
					trTxtOwnScriptCode.hide();
					trDllSite.hide();
					txtWebcode.text('');
				}
				
				function hideError()
				{
					trConnectError.hide();
					spanConnectError.text('');
				}
				
				function showSite(webCodeList)
				{
					trTxtWebcode.show();
					trTxtOwnScriptCode.show();
					trDllSite.show();
					ddlSite.empty();
					
					ddlSite.append(jQuery('<option value="">' + l10nObj.selectSite + '</option>'));
					var selected = false;
					jQuery.each(webCodeList, function(i, item) {
						selected = item.SiteId === window.kgSiteId ? ' selected="selected"' : '';
						ddlSite.append(jQuery('<option value="'+ escape(item.Value) +'" data-siteId="' + item.SiteId + '"' + selected + '>'+item.Key+'</option>'));
						
						if(selected)
						{
							hdnSiteId.val(item.SiteId);
							txtWebcode.val(item.Value);
						}
					});
					txtWebcode.text('');
					
					ddlSite.change(function(){
						var siteId = ddlSite.find('option:selected').attr('data-siteId');
						var value = unescape(ddlSite.find('option:selected').val());
						if(Kundgenerator.IsEmptyOrWhiteSpace(value))
						{
							txtWebcodeInfo.hide();
						} else {
							txtWebcodeInfo.show();
						}
						hdnSiteId.val(siteId);
						txtWebcode.val(value);
					});
				}
				
				function showWebcode(webCode)
				{
					trTxtWebcode.show();
					trTxtOwnScriptCode.show();
					txtWebcode.text(webCode);
					trDllSite.hide();
					ddlSite.empty();
					txtWebcodeInfo.show();
				}
				
				if(Kundgenerator.IsEmptyOrWhiteSpace(error))
				{
					hideError();
					
					if(webCodeList.length > 1)
					{
						showSite(webCodeList);
					} else {
						var webCode = webCodeList[0].Value;
						showWebcode(webCode);
					}
					
					Kundgenerator.SaveWebcode.Init();
				} else {
					showError(error);
				}
			} 
		});
	},
	
	GetParam: function (name, url) {
		var theItmToRtn = "";
		var theSrchStrg = location.search;
		if (url) { theSrchStrg = url; };

		var sOrig = theSrchStrg.toString();
		
		theSrchStrg = theSrchStrg.toString().toUpperCase();
		name = name.toString().toUpperCase();
		theSrchStrg = theSrchStrg.replace("?", "&");
		theSrchStrg = theSrchStrg + "&";
		var theSrchToken = "&" + name + "=";
		if (theSrchStrg.indexOf(theSrchToken) != -1) {
			var theSrchTokenLth = theSrchToken.length;
			var theSrchTokenLocStart = theSrchStrg.indexOf(theSrchToken) + theSrchTokenLth;
			var theLocOfNextAndSign = theSrchStrg.indexOf("&", theSrchTokenLocStart);
			theItmToRtn = unescape(sOrig.substring(theSrchTokenLocStart, theLocOfNextAndSign));
		}
		return unescape(theItmToRtn);
	},
	
	IsEmptyOrWhiteSpace: function (value) {
		if(value === undefined)
			return true;
	
		return value.replace(/\s+/g, ' ').replace(/\s/g, '') === '';
	},
	
	SaveWebcode:
	{
		Init:function(){
			jQuery('#btnSaveWebcode').unbind('click');
			jQuery('#btnSaveWebcode').click(function(){
				return Kundgenerator.SaveWebcode.Validate();
			});
		},
		
		Validate:function(){
			var webCode = jQuery('#txtWebcode').val();
		
			if(Kundgenerator.IsEmptyOrWhiteSpace(webCode))
			{
				alert(l10nObj.enterWebcode);
				
				if(jQuery('#ddlSite option').length > 1)
				{
					jQuery('#ddlSite').focus();
				} else {
					jQuery('#txtWebcode').focus();
				}
				
				return false;
			}
			
			return true;
		}
	}
};

jQuery('document').ready(function(){ Kundgenerator.Init(); });
/*
@package Abricos
@license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
		{name: '{C#MODNAME}', files: ['editor.js', 'lib.js']}
	]
};
Component.entryPoint = function(NS){
	
	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang;

	var buildTemplate = this.buildTemplate;
	
	var TeamEditorWidget = function(container, modname, teamid, callback, cfg){
		teamid = (teamid || 0)|0;
		cfg = L.merge({
			'override': null
		}, cfg || {});

		TeamEditorWidget.superclass.constructor.call(this, container, {
			'buildTemplate': buildTemplate, 'tnames': 'widget',
			'override': cfg['override']
		}, modname, teamid, callback, cfg);
	};
	YAHOO.extend(TeamEditorWidget, Brick.mod.widget.Widget, {
		init: function(modname, teamid, callback, cfg){
			this.teamid = teamid;
			this.callback = callback;
			this.team = null;
			this.manager = null;
		},
		buildTData: function(modname, teamid){
			return {'cledst': teamid==0?'edstnew': 'edstedit'};
		},
		onLoad: function(modname, teamid){
			
			var NSMod = Brick.mod[modname];
			if (!L.isValue(NSMod)){ return; }

			var __self = this;
			NSMod.initManager(function(man){
				__self.manager = man;
				if (teamid == 0){
					__self.onLoadTeam(new man.TeamClass({
						'dtl': {}
					}));
				}else{
					man.teamLoad(teamid, function(team){
						__self.onLoadTeam(team);
					});
				}
			});	
		},
		onClick: function(el, tp){
			switch(el.id){
			case tp['bcreate']:
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.cancel(); return true;
			}
			return false;
		},
		onLoadTeam: function(team){
			this.team = team;
			this.logoWidget = new NS.LogoWidget(this.gel('logo'), team.logo);
			this.typeSelectWidget = new NS.TeamTypeSelectWidget(this.gel('teamtype'), this.manager, {
				'value': team.type
			});
	
			this.render();
		},
		render: function(){
			var team = this.team;
			if (L.isNull(team)){ return; }
			this.elHide('loading');
			this.elShow('editor');
			
			this.elSetValue({
				'title': team.title,
				'email': team.email,
				'descript': team.descript,
				'site': team.site
			});
		},
		cancel: function(){
			NS.life(this.callback, 'cancel');
		},
		getSaveData: function(){
			return {
				'id': this.team.id,
				'uid': this.team.userid,
				'tl': this.gel('title').value,
				'eml': this.gel('email').value,
				'dsc': this.gel('descript').value,
				'site': this.gel('site').value,
				'tp': this.typeSelectWidget.getValue(),
				'logo': this.logoWidget.getValue()
			};
		},
		save: function(){
			var __self = this;
			var sd = this.getSaveData();
			
			this.elHide('btns');
			this.elShow('bloading');
			
			this.manager.teamSave(sd, function(team){
				__self.elShow('btns');
				__self.elHide('bloading');
				NS.life(__self.callback, 'save', team);
			});
		}		
	});
	NS.TeamEditorWidget = TeamEditorWidget;
	
	
	var TeamRemovePanel = function(team, callback){
		this.team = team;
		this.callback = callback;
		TeamRemovePanel.superclass.constructor.call(this, {fixedcenter: true});
	};
	YAHOO.extend(TeamRemovePanel, Brick.widget.Dialog, {
		initTemplate: function(){
			return buildTemplate(this, 'removepanel').replace('removepanel');
		},
		onClick: function(el){
			var tp = this._TId['removepanel'];
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['bremove']: this.remove(); return true;
			}
			return false;
		},
		remove: function(){
			var TM = this._TM, gel = function(n){ return  TM.getEl('removepanel.'+n); },
				__self = this;
			Dom.setStyle(gel('btns'), 'display', 'none');
			Dom.setStyle(gel('bloading'), 'display', '');
			this.team.manager.teamRemove(this.team, function(){
				__self.close();
				NS.life(__self.callback);
			});
		}
	});
	NS.TeamRemovePanel = TeamRemovePanel;

};
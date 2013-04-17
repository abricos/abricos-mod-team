/* 
@package Abricos
*/

var Component = new Brick.Component();
Component.requires = { 
	mod:[
        {name: 'sys', files: ['item.js']},
        {name: 'widget', files: ['lib.js']},
        {name: 'uprofile', files: ['lib.js']},
        {name: '{C#MODNAME}', files: ['roles.js']}
	]
};
Component.entryPoint = function(NS){

	var Dom = YAHOO.util.Dom,
		L = YAHOO.lang,
		R = NS.roles;

	var CE = YAHOO.util.CustomEvent;
	var SysNS = Brick.mod.sys;
	var LNG = this.language;
	var UP = Brick.mod.uprofile;

	this.buildTemplate({}, '');
	
	NS.lif = function(f){return L.isFunction(f) ? f : function(){}; };
	NS.life = function(f,p1,p2,p3,p4,p5,p6,p7){
		f=NS.lif(f); f(p1,p2,p3,p4,p5,p6,p7);
	};
	NS.Item = SysNS.Item;
	NS.ItemList = SysNS.ItemList;
	
	NS.emailValidate = function(email) { 
		var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	    return re.test(email);
	};
	
	var Team = function(d){
		d = L.merge({
			'm': 'team',
			'nm': '',
			'eml': '',
			'tl': '',
			'dsc': '',
			'site': '',
			'logo': '',
			'mcnt': 0,
			'auid': Brick.env.user.id,
			'role': {}
		}, d || {});
		Team.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Team, SysNS.Item, {
		init: function(d){
			this.manager = Manager.get(d['m']);
			this.role = new this.manager['TeamUserRoleClass'](d['role']);
			this.detail = null;
			
			Team.superclass.init.call(this, d);
		},
		update: function(d){
			this.module		= d['m'];
			this.name		= d['nm'];
			this.authorid	= d['auid'];			// создатель сообщества
			this.title		= d['tl'];
			this.descript	= d['dsc'];
			this.site		= d['site'];
			this.email		= d['eml'];
			this.logo		= d['logo'].length == 8 ? d['logo'] : null;
			this.memberCount = d['mcnt']*1;

			this.siteHTML = this.siteURL = '';
			if (L.isString(this.site) && this.site.length > 3){
				this.siteURL = 'http://'+this.site;
				this.siteHTML = "<a href='"+this.siteURL+"' target='_blank'>"+this.site+"</a>";
			}
			this.role.update(d['role']);
			
			if (d['dtl'] && !L.isNull(d['dtl'])){
				this.detail = new this.manager['TeamDetailClass'](d['dtl']);
			}
		},
		load: function(callback, reload){ // загрузить полные данные
			NS.Manager.fire(this.module, 'teamLoad', this.id, callback, reload);
		}
	});
	NS.Team = Team;
	
	var TeamList = function(d, teamClass){
		TeamList.superclass.constructor.call(this, d, teamClass || Team);
	};
	YAHOO.extend(TeamList, SysNS.ItemList, {});
	NS.TeamList = TeamList;
	
	var TeamDetail = function(d){
		d = L.merge({
			'iwCount': 0
		}, d || {});
		this.init(d);
	};
	TeamDetail.prototype = {
		init: function(d){
			this.update(d);
		},
		update: function(d){
			this.inviteWaitCount = d['iwCount'];
		}
	};
	NS.TeamDetail = TeamDetail;


	var TeamUserRole = function(d){
		d = L.merge({
			'ismbr': 0,
			'isadm': 0,
			'isinv': 0,
			'isjrq': 0,
			'ruid': 0
		}, d || {});
		this.init(d);
	};
	TeamUserRole.prototype = {
		init: function(d){
			this.update(d);
		},
		update: function(d){
			this.isMember = d['ismbr']*1==1;
			this.isAdmin = d['isadm']*1==1;
			
			this.isJoinRequest = !this.isMember && d['isjrq']*1==1;
			this.isInvite = !this.isMember && d['isinv']*1==1;
			this.relUserId = d['ruid']*1;
		}
	};
	NS.TeamUserRole = TeamUserRole;

	var Member = function(team, d){
		this.team = team;
		d = L.merge({
			'role': {}
		}, d || {});
		Member.superclass.constructor.call(this, d);
	};
	YAHOO.extend(Member, SysNS.Item, {
		init: function(d){
			Member.superclass.init.call(this, d);
		},
		update: function(d){
			this.role = new this.team.manager.TeamUserRoleClass(d['role']);
		}
	});
	NS.Member = Member;
	
	var MemberList = function(d){
		MemberList.superclass.constructor.call(this, d, Member);
	};
	YAHOO.extend(MemberList, SysNS.ItemList, {});
	NS.MemberList = MemberList;
	
	var UserConfig = function(d){
		d = L.merge({
			'iwCount': 0,
			'iwLimit': 0
		}, d || {});
		this.init(d);
	};
	UserConfig.prototype = {
		init: function(d){
			this.needUpdate = true;
			this.update(d);
		},
		update: function(d){
			this.inviteWaitCount = d['iwCount'];
			this.inviteWaitLimit = d['iwLimit'];
		}
	};
	NS.UserConfig = UserConfig;

	var Manager = function(modname, callback, cfg){
		this.modname = modname;
		cfg = L.merge({
			'UserConfigClass':		UserConfig,
			'TeamClass':			Team,
			'TeamDetailClass':		TeamDetail,
			'TeamListClass':		TeamList,
			'TeamUserRoleClass':	TeamUserRole,
			'MemberClass':			Member,
			'MemberListClass':		MemberList
		}, cfg || {});
		
		this.init(callback, cfg);
	};
	Manager.prototype = {
		init: function(callback, cfg){
			this.UserConfigClass	= cfg['UserConfigClass'];
			
			this.TeamClass			= cfg['TeamClass'];
			this.TeamDetailClass	= cfg['TeamDetailClass'];
			this.TeamListClass		= cfg['TeamListClass'];
			this.TeamUserRoleClass	= cfg['TeamUserRoleClass'];
			
			this.MemberClass		= cfg['MemberClass'];
			this.MemberListClass	= cfg['MemberListClass'];
			
			this.invite = null;
			this.userConfig = new this.UserConfigClass();
			this._cacheTeam = {};
			
			this.users = UP.viewer.users;
			
			// глобальный кеш групп
			this._teamCache = new this.TeamListClass();
		},
		
		ajax: function(d, callback){
			d = d || {};
			d['tm'] = Math.round((new Date().getTime())/1000);
			
			var userConfig = this.userConfig;
			if (userConfig.needUpdate){
				d['userconfigupdate'] = true;
				userConfig.needUpdate = false;
			}
			var __self = this;
			Brick.ajax(this.modname, {
				'data': d,
				'event': function(request){
					var d = !L.isNull(request) && !L.isNull(request.data) ? request.data : null,
						result = !L.isNull(d) ? (d.result ? d.result : null) : null;
		
					if (!L.isNull(d)){
						if (d['userconfig']){
							userConfig.update(d['userconfig']);
						}
						if (d['users']){
							__self.users.update(d['users']);
						}
					}
					NS.life(callback, result);
				}
			});
		},
		
		_updateTeamList: function(d){
			if (!d || !d['teams'] || !L.isArray(d['teams']['list'])){ 
				return null; 
			}
			
			var dList = d['teams']['list'];
			
			var cache = this._teamCache,
				list = new this.TeamListClass();
			
			for (var i=0;i<dList.length;i++){
				var di = dList[i], team = cache.get(di['id']);
				
				if (L.isNull(team)){
					team = new this.TeamClass(di);
					cache.add(team);
				}else{
					team.update(di);
				}
				list.add(team);
			}
			return list;
		},
		
		teamListLoad: function(callback, prm){
			prm = L.merge({
				'page': 1,
				'memberid': 0
			}, prm||{});
			prm['do'] = 'teamlist';
			
			var __self = this;
			
			this.ajax(prm, function(d){
				var list = __self._updateTeamList(d);
				NS.life(callback, list);
			});
		},
		
		teamSave: function(sd, callback){
			var __self = this;
			sd['do'] = 'teamsave';
			
			this.ajax(sd, function(d){
				var list = __self._updateTeamList(d);
				NS.life(callback, list);
			});
		},
		
		teamLoad: function(teamid, callback, cfg){
			cfg = L.merge({
				'reload': false,
				'other': null
			}, cfg || {});
			
			var __self = this,
				cache = this._teamCache,
				team = cache.get(teamid);

			if (!L.isNull(team) && !L.isNull(team.detail) && !cfg['reload']){
				NS.life(callback, team);
				return;
			}
			
			var rq = {
				'do': 'team',
				'teamid': teamid
			};
			if (!L.isNull(cfg['other'])){
				rq['other'] = cfg['other'];
			}
			this.ajax(rq, function(d){
				if (d && !L.isNull(d) && d['team']){
					if (L.isNull(team)){
						team = new __self.TeamClass(d['team']);
						cache.add(team);
					}else{
						team.update(d['team']);
					}
				}
				NS.life(callback, team);
			});
		},
		
		teamRemove: function(team, callback){
			if (L.isNull(team)){
				NS.life(callback);
				return;
			}
			this.ajax({'do': 'teamremove', 'teamid': team.id}, function(d){
				NS.life(callback);
			});
		},
		
		_updateMember: function(d){
			if (L.isNull(d)){
				return null;
				this.users.update([d]);
			}

			return new this.MemberClass(d);
		},
		
		memberLoad: function(team, memberid, callback){
			if (L.isNull(team)){
				NS.life(callback, null);
				return;
			}
			var __self = this;
			this.ajax({
				'do': 'member',
				'teamid': team.id,
				'memberid': memberid
			}, function(d){
				var member = __self._updateMember(d);
				NS.life(callback, member);
			});
		},
		
		memberListLoad: function(team, callback){
			if (L.isNull(team)){
				NS.life(callback, null);
				return;
			}
			var __self = this;
			this.ajax({
				'do': 'memberlist',
				'teamid': team.id
			}, function(d){
				var list = null;
				
				if (L.isValue(d) && L.isValue(d['members'])){
					
					list = new __self.MemberListClass();
					
					var dList = d['members']['list'];
					for (var i=0; i<dList.length; i++){
						list.add(new __self.MemberClass(team, dList[i]));
					}
				}
				
				NS.life(callback, list);
			});
		},

		memberSave: function(team, sd, callback){
			sd['do'] = 'membersave';
			sd['teamid'] = team.id;
			var __self = this;
			this.ajax(sd, function(d){
				var member = __self._updateMember(d);
				NS.life(callback, member);
			});
		}		
		
	};
	Manager.get = function(modname){
		var man = Brick.mod[modname]['manager'];
		if (!L.isObject(man)){
			man = NS.manager;
		}
		return man;
	};
	Manager.fire = function(modname, fname, p1, p2, p3, p4, p5, p6, p7, p8){
		var func = Manager.get(modname)[fname];
		if (L.isFunction(func)){
			func(p1, p2, p3, p4, p5, p6, p7, p8);
		}
	};
	NS.Manager = Manager;

};
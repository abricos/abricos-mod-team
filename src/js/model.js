var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.ATTRIBUTE = {
        teamApp: {
            readOnly: true,
            getter: function(){
                var app = this.get('appInstance');
                if (!app){
                    return null;
                }
                if (app.get('moduleName') === 'team'){
                    return app;
                }
                return app.getApp('team');
            }
        },
        teamid: {
            value: 0,
            setter: function(val){
                return val | 0;
            }
        },
        team: {value: null},
        teamListFilter: {
            value: {},
            setter: function(val){
                return val || {};
            }
        },
        policy: {},
        memberid: {
            value: 0,
            setter: function(val){
                return val | 0;
            }
        },
        member: {value: null},
        memberList: {value: null},
        memberListFilter: {
            getter: function(val){
                return Y.merge(val || {}, {
                    teamid: this.get('team').get('id'),
                    policy: this.get('policy')
                });
            }
        },
        inviteApp: {
            readOnly: true,
            getter: function(){
                if (!Brick.mod['invite']){
                    return null;
                }
                return Brick.mod.invite.appInstance;
            }
        },
        user: {
            readOnly: true,
            getter: function(){
                if (this._cacheUser){
                    return this._cacheUser;
                }
                var userid = this.attrAdded('userid') ? this.get('userid') : this.get('id'),
                    userList = this.appInstance.getApp('uprofile').get('userList');

                return this._cacheUser = userList.getById(userid);
            }
        }
    };

    NS.Team = Y.Base.create('team', SYS.AppModel, [], {
        structureName: 'Team',
        isAction: function(action){
            var a = action.split('.'),
                obj = this.get('role');

            for (var i = 0, s; i < a.length; i++){
                s = a[i];
                if (!obj[s]){
                    return false;
                }
                obj = obj[s];
            }
            return !!obj;
        }
    }, {
        ATTRS: {
            extends: {value: {}},
            role: {value: {}}
        }
    });

    NS.TeamList = Y.Base.create('teamList', SYS.AppModelList, [], {
        appItem: NS.Team,
    });

    NS.Policy = Y.Base.create('policy', SYS.AppModel, [], {
        structureName: 'Policy',
    }, {
        ADMIN: 'admin',
        GUEST: 'guest'
    });

    NS.PolicyList = Y.Base.create('policyList', SYS.AppModelList, [], {
        appItem: NS.Policy,
    });

    NS.Action = Y.Base.create('action', SYS.AppModel, [], {
        structureName: 'Action',
    }, {
        ATTRS: {
            title: {value: ''}
        }
    });

    NS.ActionList = Y.Base.create('actionList', SYS.AppModelList, [], {
        appItem: NS.Action,
    });

    NS.Role = Y.Base.create('role', SYS.AppModel, [], {
        structureName: 'Role',
    });

    NS.RoleList = Y.Base.create('roleList', SYS.AppModelList, [], {
        appItem: NS.Role,
    });

    NS.TeamListFilter = Y.Base.create('teamListFilter', SYS.AppResponse, [], {
        structureName: 'TeamListFilter'
    });

    NS.Member = Y.Base.create('member', SYS.AppModel, [], {
        structureName: 'Member',
        initializer: function(d){
            d = d || {};
            this.set('extends', d.extends || {});
        },
        toReplace: function(){
            var user = this.get('user'),
                ret = {
                    id: this.get('id'),
                    teamid: this.get('teamid'),
                    userid: this.get('userid'),
                    userViewName: user.get('viewName'),
                    userViewURL: user.get('viewURL'),
                    userAvatarSrc24: user.get('avatarSrc24'),
                    userAvatarSrc45: user.get('avatarSrc45'),
                    userAvatarSrc90: user.get('avatarSrc90'),
                    userAvatarSrc18: user.get('avatarSrc180'),
                },
                exts = this.get('extends');

            return ret;
        },
    }, {
        ATTRS: {
            user: NS.ATTRIBUTE.user,
            extends: {}
        }
    });

    NS.MemberList = Y.Base.create('memberList', SYS.AppModelList, [], {
        appItem: NS.Member,
    });

    NS.MemberListFilter = Y.Base.create('MemberListFilter', SYS.AppResponse, [], {
        structureName: 'MemberListFilter'
    });

    NS.Plugin = Y.Base.create('plugin', SYS.AppModel, [], {
        structureName: 'Plugin',
    });

    NS.PluginList = Y.Base.create('pluginList', SYS.AppModelList, [], {
        appItem: NS.Plugin,
        getModules: function(team){
            var mods = ['team', team.get('module')];
            this.each(function(plugin){
                if (plugin.get('isCommunity')){
                    return;
                }
                mods[mods.length] = plugin.get('id');
            }, this);
            return mods;
        },
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });
};

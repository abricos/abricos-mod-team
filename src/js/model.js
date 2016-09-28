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
        memberid: {
            value: 0,
            setter: function(val){
                return val | 0;
            }
        },
        member: {value: null},
        memberList: {value: null},
        memberListFilter: {
            setter: function(val){
                val = Y.merge({
                    teamid: 0
                }, val || {});
                return val;
            },
            getter: function(val){
                var team = this.get('team');
                if (!team){
                    return val;
                }
                return {
                    method: 'team',
                    teamid: team.get('id')
                };
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
                var userid = this.get('userid'),
                    userList = this.appInstance.getApp('uprofile').get('userList');

                return userList.getById(userid);
            }
        }
    };

    NS.TeamUserRole = Y.Base.create('teamUserRole', SYS.AppModel, [], {
        structureName: 'TeamUserRole',
        isJoined: function(){
            return this.get('status') === 'joined';
        },
        isWaiting: function(){
            return this.get('status') === 'waiting';
        },
        isAdmin: function(){
            return NS.roles.isAdmin ||
                (this.get('role') === 'admin' && this.isJoined());
        },
    });

    NS.Team = Y.Base.create('team', SYS.AppModel, [], {
        structureName: 'Team',
    }, {
        ATTRS: {
            extends: {value: {}}
        }
    });

    NS.TeamList = Y.Base.create('teamList', SYS.AppModelList, [], {
        appItem: NS.Team,
    });

    NS.Group = Y.Base.create('group', SYS.AppModel, [], {
        structureName: 'Group',
    }, {
        ATTRS: {
            extends: {value: {}}
        }
    });

    NS.GroupList = Y.Base.create('groupList', SYS.AppModelList, [], {
        appItem: NS.Group,
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
        myIsAdmin: function(){
            return this.get('myStatus') === 'joined' && this.get('myRole') === 'admin';
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
            var mods = [team.get('module')];
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

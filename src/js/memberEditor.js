var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var MemberEditorWidgetExt = function(){
    };
    MemberEditorWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.inviteApp,

        teamid: NS.ATTRIBUTE.teamid,
        memberid: NS.ATTRIBUTE.teamid,
        member: NS.ATTRIBUTE.member,

        callback: {value: null},
        callbackContext: {value: null},

        isEdit: {
            getter: function(){
                return this.get('memberid') > 0;
            }
        }
    };
    MemberEditorWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            var teamApp = this.get('teamApp'),
                teamid = this.get('teamid'),
                memberid = this.get('memberid'),
                member;

            this.set('waiting', true);
            if (memberid > 0){
                teamApp.member(teamid, memberid, function(err, result){
                    member = err ? null : result.member;
                    this._onLoadMember(result.member);
                }, this);
            } else {
                member = new NS.Member({
                    appInstance: teamApp,
                    teamid: this.get('teamid'),
                    module: appInstance.get('moduleName')
                });

                NS.initApps('invite', function(){
                    this._onLoadMember(member);
                }, this);
            }
        },
        _onLoadMember: function(member){
            this.set('waiting', false);
            this.set('member', member);

            if (!member){
                // TODO: show member not found error
                return;
            }

            var tp = this.template;

            if (tp.one('inviteByEmailWidget')){
                this.addWidget('inviteByEmail', new NS.MemberInviteByEmailWidget({
                    srcNode: tp.one('inviteByEmailWidget'),
                    callbackContext: this,
                    cleanCallback: function(){
                        tp.hide('editorPanel,inviteInfo,inviteButton');
                    },
                    callback: function(sr){
                        if (sr.userid === 0){
                            tp.show('editorPanel,inviteInfo,inviteButton');
                        }
                    }
                }));
            }

            this.onLoadMember(member);
        },
        onLoadMember: function(member){
        },
        save: function(){
            var tp = this.template,
                member = this.get('member'),
                teamid = member.get('teamid'),
                callback = this.get('callback'),
                data = {
                    id: member.get('id'),
                    findEmail: tp.getValue('findEmail'),
                    firstName: tp.getValue('firstName'),
                    lastName: tp.getValue('lastName'),
                    extends: {
                        postid: this.getWidget('postSelect').getValue(),
                        deptid: this.getWidget('deptSelect').getValue()
                    }
                };

            this.set('waiting', true);
            this.get('teamApp').memberSave(teamid, data, function(err, result){
                this.set('waiting', false);

                var memberList = err ? null : result.memberList;
                if (Y.Lang.isFunction(callback)){
                    callback.call(this.get('callbackContext'), memberList);
                }
            }, this);
        },
        cancel: function(){
            var callback = this.get('callback');
            if (Y.Lang.isFunction(callback)){
                callback.call(this.get('callbackContext'), null);
            }
        },
        onClick: function(e){
            switch(e.dataClick){
                case 'save':
                    this.save();
                    return true;
            }
        }
    };
    NS.MemberEditorWidgetExt = MemberEditorWidgetExt;

    NS.MemberInviteByEmailWidget = Y.Base.create('MemberInviteWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.set('waiting', true);
            NS.initApps('invite', function(){
                this.set('waiting', false);
            }, this);
        },
        findUserByEmail: function(){
            var tp = this.template,
                inviteApp = this.get('inviteApp'),
                cleanCallback = this.get('cleanCallback'),
                callback = this.get('callback'),
                callbackContext = this.get('callbackContext') || null,
                email = tp.getValue('findEmail');

            if (Y.Lang.isFunction(cleanCallback)){
                cleanCallback.call(callbackContext);
            }

            tp.hide('findEmailNotValid,findNotInvite,findUserNotFound');

            this.set('waiting', true);
            inviteApp.userByEmail(email, function(err, result){
                this.set('waiting', false);

                if (err){
                    return;
                }

                var sr = result.userByEmail;
                if (sr.isNotValid){
                    return tp.show('findEmailNotValid');
                }

                if (sr.isNotInvite){
                    return tp.show('findNotInvite');
                }

                if (sr.userid === 0){
                    tp.setHTML('email', email);
                    tp.show('findUserNotFound');
                }

                if (Y.Lang.isFunction(callback)){
                    callback.call(callbackContext, sr);
                }
            }, this);
        },
        toJSON: function(){
            var tp = this.template;
            return {
                email: tp.getValue('findEmail')
            };
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'inviteByEmail'},
            inviteApp: NS.ATTRIBUTE.inviteApp,
            cleanCallback: {value: null},
            callback: {value: null},
            callbackContext: {value: null},
        },
        CLICKS: {
            findUserByEmail: 'findUserByEmail',
        }
    });
};
